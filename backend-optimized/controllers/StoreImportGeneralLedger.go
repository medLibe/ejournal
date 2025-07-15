package controllers

import (
	"errors"
	"fmt"
	"net/http"
	"time"

	"backend-optimized/config"
	"backend-optimized/models"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
)

type ImportLedgerRequest struct {
	DB            string             `json:"db"`
	User          string             `json:"user"`
	GeneralLedger []models.LedgerRow `json:"general_ledger"`
}

func StoreImportGeneralLedgerHandler(c *gin.Context) {
	var req ImportLedgerRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Gagal membaca data JSON: " + err.Error(),
		})
		return
	}

	db, err := config.InitDBWithName(req.DB)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  false,
			"message": "Gagal koneksi ke database: " + err.Error(),
		})
		return
	}

	rows := req.GeneralLedger
	if len(rows) == 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Data tidak boleh kosong.",
		})
		return
	}

	createdBy := req.User
	if createdBy == "" {
		createdBy = "system"
	}

	now := time.Now()
	importNo := "TRX" + now.Format("20060102150405")

	importRecord := models.GeneralLedgerImport{
		ImportDate: now,
		ImportNo:   importNo,
		CreatedBy:  createdBy,
		UpdatedBy:  createdBy,
		CreatedAt:  now,
		UpdatedAt:  now,
	}
	if err := db.Create(&importRecord).Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  false,
			"message": "Gagal membuat import record.",
		})
		return
	}
	importID := importRecord.ID

	// --- Step 1: validate all row ---
	validated := make([]models.ValidatedLedgerRow, 0, len(rows))
	accountSet := map[uint]struct{}{}
	periodeSet := map[string]struct{}{}

	for _, row := range rows {
		vrow, err := validateAccountAndPrepareData(db, row)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"status": false, "message": err.Error()})
			return
		}
		validated = append(validated, vrow)
		accountSet[vrow.IDAkun] = struct{}{}
		periodeSet[vrow.Periode] = struct{}{}
	}

	accountIDs := make([]uint, 0, len(accountSet))
	for id := range accountSet {
		accountIDs = append(accountIDs, id)
	}

	periodes := make([]string, 0, len(periodeSet))
	for p := range periodeSet {
		periodes = append(periodes, p)
	}

	// --- Step 2: get all period balances ---
	var existingPB []models.PeriodeBalance
	db.Where("account_id IN ? AND periode IN ?", accountIDs, periodes).Find(&existingPB)

	pbMap := make(map[string]models.PeriodeBalance)
	for _, pb := range existingPB {
		key := fmt.Sprintf("%d_%s", pb.AccountID, pb.Periode)
		pbMap[key] = pb
	}

	// --- Step 3: prepare insert/update ---
	var glBatch []models.GeneralLedger
	newBalances := make(map[uint]float64)
	pbInsert := []models.PeriodeBalance{}
	pbUpdate := []models.PeriodeBalance{}

	for _, row := range validated {
		key := fmt.Sprintf("%d_%s", row.IDAkun, row.Periode)
		pb, exists := pbMap[key]
		opening := row.OpeningBalance
		if exists {
			opening = pb.ClosingBalance
		}

		newBalance := updateAccountBalance(row, opening, false)
		newBalances[row.IDAkun] = newBalance

		glBatch = append(glBatch, models.GeneralLedger{
			ImportID:        importID,
			TransactionDate: parseDate(row.Tanggal),
			Periode:         row.Periode,
			Reference:       row.TipeSumber,
			ReferenceNo:     row.NomorSumber,
			Department:      row.Department,
			AccountID:       row.IDAkun,
			Amount:          row.Amount,
			TransactionType: row.TipeTransaksi,
			Description:     row.Keterangan,
			CreatedAt:       now,
			UpdatedAt:       now,
		})

		if exists {
			pb.ClosingBalance = newBalance
			pb.UpdatedAt = now
			pbUpdate = append(pbUpdate, pb)
		} else {
			pbInsert = append(pbInsert, models.PeriodeBalance{
				AccountID:      row.IDAkun,
				Periode:        row.Periode,
				OpeningBalance: opening,
				ClosingBalance: newBalance,
				CreatedAt:      now,
				UpdatedAt:      now,
			})
		}
	}

	// --- Step 4: save to DB ---
	if err := db.CreateInBatches(glBatch, 100).Error; err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"status":  false,
			"message": "Gagal simpan general ledger.",
		})
		return
	}

	for id, bal := range newBalances {
		db.Model(&models.Account{}).
			Where("id = ?", id).
			Update("opening_balance", bal)
	}

	for _, pb := range pbUpdate {
		db.Model(&models.PeriodeBalance{}).
			Where("id = ?", pb.ID).
			Update("closing_balance", pb.ClosingBalance)
	}

	if len(pbInsert) > 0 {
		db.CreateInBatches(pbInsert, 100)
	}

	c.JSON(http.StatusOK, gin.H{
		"status":  true,
		"message": "Import berhasil.",
	})
}

func validateAccountAndPrepareData(db *gorm.DB, row models.LedgerRow) (models.ValidatedLedgerRow, error) {
	var account models.Account

	err := db.Preload("AccountType.AccountGroup").
		Where("account_code = ?", row.NomorAkun).
		First(&account).Error

	if errors.Is(err, gorm.ErrRecordNotFound) {
		return models.ValidatedLedgerRow{}, fmt.Errorf("akun dengan nomor %s tidak ditemukan", row.NomorAkun)
	}

	periode := parseDate(row.Tanggal).Format("200601")

	return models.ValidatedLedgerRow{
		Tanggal:        row.Tanggal,
		TipeSumber:     row.TipeSumber,
		NomorSumber:    row.NomorSumber,
		Department:     row.Department,
		NomorAkun:      row.NomorAkun,
		IDAkun:         account.ID,
		Amount:         row.Nominal,
		TipeTransaksi:  row.TipeTransaksi,
		Keterangan:     row.Keterangan,
		NormalBalance:  account.AccountType.AccountGroup.NormalBalance,
		OpeningBalance: account.OpeningBalance,
		Periode:        periode,
	}, nil
}

func updateAccountBalance(row models.ValidatedLedgerRow, opening float64, reverse bool) float64 {
	n := row.NormalBalance
	t := row.TipeTransaksi
	a := row.Amount

	if n == 1 { // Debit
		if t == 1 {
			if reverse {
				return opening - a
			}
			return opening + a
		}
		if reverse {
			return opening + a
		}
		return opening - a
	}

	if n == 2 { // Kredit
		if t == 2 {
			if reverse {
				return opening - a
			}
			return opening + a
		}
		if reverse {
			return opening + a
		}
		return opening - a
	}

	return opening
}

func parseDate(dateStr string) time.Time {
	layout := "2006-01-02"
	t, err := time.Parse(layout, dateStr)
	if err != nil {
		return time.Now()
	}
	return t
}
