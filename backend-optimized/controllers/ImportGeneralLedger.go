package controllers

import (
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/xuri/excelize/v2"

	"backend-optimized/config"
	"backend-optimized/models"
)

func ImportGeneralLedgerHandler(c *gin.Context) {
	targetDB := c.PostForm("db")
	if targetDB == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Parameter 'db' tidak dikirim.",
		})
		return
	}

	db, err := config.InitDBWithName(targetDB)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Gagal koneksi ke database: " + err.Error(),
		})
		return
	}

	file, err := c.FormFile("file_general_ledger")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "File tidak ditemukan.",
		})
		return
	}

	src, err := file.Open()
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Gagal membuka file.",
		})
	}
	defer src.Close()

	f, err := excelize.OpenReader(src)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Gagal membuka file excel.",
		})
		return
	}

	sheets := f.GetSheetList()
	if len(sheets) == 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Tidak ada sheet dalam file Excel.",
		})
		return

	}
	rows, err := f.GetRows(sheets[0])
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Gagal membaca sheet Excel.",
		})
		return
	}

	if len(rows) == 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"status":  false,
			"message": "Sheet kosong.",
		})
		return
	}

	// mapping column
	headerAliases := map[string]string{
		"nomor akun":   "nomor_akun",
		"nama akun":    "nama_akun",
		"tanggal":      "tanggal",
		"debit":        "debit",
		"kredit":       "kredit",
		"tipe sumber":  "tipe_sumber",
		"nomor sumber": "nomor_sumber",
		"keterangan":   "keterangan",
		"department":   "department", // optional
	}

	requiredHeaders := []string{
		"tanggal", "nomor_akun", "tipe_sumber",
		"nomor_sumber", "debit", "kredit", "keterangan",
	}

	header := rows[0]
	colIndex := map[string]int{}

	for idx, col := range header {
		colName := normalizeHeader(col)
		if internalName, ok := headerAliases[colName]; ok {
			colIndex[internalName] = idx
		}
	}

	var errors []string
	for _, req := range requiredHeaders {
		if _, ok := colIndex[req]; !ok {
			errors = append(errors, fmt.Sprintf("Kolom '%s' tidak ditemukan dalam file.", req))
		}
	}
	if len(errors) > 0 {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  false,
			"message": "Format file tidak sesuai. " + strings.Join(errors, " "),
		})
		return
	}

	var preview []models.LedgerRow
	var totalDebit, totalKredit float64

	for _, row := range rows[1:] {
		if len(row) == 0 || len(row) <= colIndex["nomor_akun"] {
			continue
		}

		akun := strings.TrimSpace(row[colIndex["nomor_akun"]])
		if akun == "" {
			continue
		}

		var account models.Account
		err = db.Where("account_code = ?", akun).First(&account).Error
		if err != nil {
			c.JSON(http.StatusUnprocessableEntity, gin.H{
				"status":  false,
				"message": fmt.Sprintf("Nomor akun '%s' tidak ditemukan.", akun),
			})
			continue
		}

		// nominal checking
		debitStr := strings.ReplaceAll(row[colIndex["debit"]], ",", "")
		kreditStr := strings.ReplaceAll(row[colIndex["kredit"]], ",", "")
		debit, _ := strconv.ParseFloat(debitStr, 64)
		kredit, _ := strconv.ParseFloat(kreditStr, 64)

		var nominal float64
		var tipeTransaksi int
		if debit != 0 {
			nominal = debit
			tipeTransaksi = 1
			totalDebit += nominal
		} else if kredit != 0 {
			nominal = kredit
			tipeTransaksi = 2
			totalKredit += nominal
		} else {
			continue
		}

		// date conversion
		tanggalRaw := row[colIndex["tanggal"]]
		tanggal, err := convertExcelDate(tanggalRaw)
		if err != nil {
			c.JSON(http.StatusBadRequest, gin.H{
				"status":  false,
				"message": fmt.Sprintf("Tanggal tidak valid: %s", tanggalRaw),
			})
			return
		}

		entry := models.LedgerRow{
			Tanggal:       tanggal.Format("2006-01-02"),
			NomorAkun:     akun,
			NamaAkun:      row[colIndex["nama_akun"]],
			Department:    row[colIndex["department"]],
			NomorSumber:   row[colIndex["nomor_sumber"]],
			TipeSumber:    row[colIndex["tipe_sumber"]],
			Nominal:       nominal,
			Keterangan:    row[colIndex["keterangan"]],
			TipeTransaksi: tipeTransaksi,
			IDAkun:        account.ID,
		}

		preview = append(preview, entry)
	}

	if int(totalDebit) != int(totalKredit) {
		c.JSON(http.StatusConflict, gin.H{
			"status":  false,
			"message": fmt.Sprintf("Total debit dan kredit tidak balance. Debit: %.2f, Kredit: %.2f", totalDebit, totalKredit),
		})
		return
	}

	// return response
	c.JSON(http.StatusOK, gin.H{
		"status":        true,
		"preview":       preview,
		"total":         len(preview),
		"total_nominal": totalDebit,
	})
}

func normalizeHeader(s string) string {
	replacer := strings.NewReplacer(
		"\u00a0", "", // non-breaking space
		"\u200b", "", // zero-width space
		"\ufeff", "", // BOM
		"\n", "",
		"\r", "",
	)
	s = replacer.Replace(s)
	s = strings.ToLower(s)
	s = strings.TrimSpace(s)
	return s
}

func convertExcelDate(value string) (time.Time, error) {
	if serial, err := strconv.ParseFloat(value, 64); err == nil {
		// Excel serial date
		return time.Date(1899, 12, 30, 0, 0, 0, 0, time.UTC).Add(time.Duration(serial*86400) * time.Second), nil
	}
	// Assume format like "2024-05-22"
	return time.Parse("2006-01-02", value)
}
