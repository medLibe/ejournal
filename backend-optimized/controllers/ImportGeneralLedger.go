package controllers

import (
	"fmt"
	"net/http"
	"regexp"
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
	missingAccounts := map[string]bool{}

	for i, row := range rows[1:] {
		lineNumber := i + 2

		// Check length row
		if len(row) <= colIndex["nomor_akun"] {
			errors = append(errors, fmt.Sprintf("Baris %d: Kolom 'nomor akun' tidak ditemukan atau kosong.", lineNumber))
			continue
		}

		akun := strings.TrimSpace(row[colIndex["nomor_akun"]])
		if akun == "" {
			errors = append(errors, fmt.Sprintf("Baris %d: Nomor akun kosong.", lineNumber))
			continue
		}

		// check if account exist in db
		var account models.Account
		err = db.Where("account_code = ?", akun).First(&account).Error
		if err != nil {
			errors = append(errors, fmt.Sprintf("Baris %d: Nomor akun '%s' tidak ditemukan di database.", lineNumber, akun))
			missingAccounts[akun] = true
			continue
		}

		rawDebit := row[colIndex["debit"]]
		rawKredit := row[colIndex["kredit"]]
		debitStr := cleanNumeric(rawDebit)
		kreditStr := cleanNumeric(rawKredit)

		debit, errDebit := strconv.ParseFloat(debitStr, 64)
		kredit, errKredit := strconv.ParseFloat(kreditStr, 64)

		if errDebit != nil && errKredit != nil {
			errors = append(errors, fmt.Sprintf("Baris %d: Gagal parsing nilai debit/kredit.", lineNumber))
			continue
		}

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
			errors = append(errors, fmt.Sprintf("Baris %d: Nilai debit dan kredit keduanya nol.", lineNumber))
			continue
		}

		// convert date
		tanggalRaw := row[colIndex["tanggal"]]
		tanggal, err := convertExcelDate(tanggalRaw)
		if err != nil {
			errors = append(errors, fmt.Sprintf("Baris %d: Tanggal tidak valid: %s", lineNumber, tanggalRaw))
			continue
		}

		// make entry
		entry := models.LedgerRow{
			Tanggal:       tanggal.Format("2006-01-02"),
			NomorAkun:     akun,
			NamaAkun:      safeGet(row, colIndex, "nama_akun"),
			Department:    safeGet(row, colIndex, "department"),
			NomorSumber:   safeGet(row, colIndex, "nomor_sumber"),
			TipeSumber:    safeGet(row, colIndex, "tipe_sumber"),
			Nominal:       nominal,
			Keterangan:    safeGet(row, colIndex, "keterangan"),
			TipeTransaksi: tipeTransaksi,
			IDAkun:        account.ID,
		}

		preview = append(preview, entry)
	}

	fmt.Printf("[DEBUG] Total Debit=%.2f | Total Kredit=%.2f | Selisih=%.2f\n", totalDebit, totalKredit, totalDebit-totalKredit)

	if len(preview) == 0 {
		msg := "Semua baris gagal diproses."
		if len(errors) > 0 {
			msg += " Kesalahan: " + strings.Join(errors, " ")
		}

		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  false,
			"message": msg,
		})
		return
	}

	if len(missingAccounts) > 0 {
		c.JSON(http.StatusUnprocessableEntity, gin.H{
			"status":  false,
			"message": strings.Join(errors, " "),
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
		// excel serial date
		return time.Date(1899, 12, 30, 0, 0, 0, 0, time.UTC).Add(time.Duration(serial*86400) * time.Second), nil
	}
	// assume format like "2024-05-22"
	return time.Parse("2006-01-02", value)
}

func safeGet(row []string, colIndex map[string]int, key string) string {
	idx, ok := colIndex[key]
	if !ok || idx >= len(row) {
		return ""
	}
	return row[idx]
}

func cleanNumeric(input string) string {
	re := regexp.MustCompile(`[^0-9,.\-]`)
	cleaned := re.ReplaceAllString(input, "")

	if cleaned == "" || cleaned == "-" {
		return "0"
	}

	// if there are 2 separators (comma & point), let assums:
	// - thousand separator: appear first
	// - decimal separator: appear last
	if strings.Contains(cleaned, ",") && strings.Contains(cleaned, ".") {
		lastComma := strings.LastIndex(cleaned, ",")
		lastDot := strings.LastIndex(cleaned, ".")
		if lastComma > lastDot {
			// comma to be decimal, delete thousand point
			cleaned = strings.ReplaceAll(cleaned, ".", "")
			cleaned = strings.ReplaceAll(cleaned, ",", ".")
		} else {
			// point to be decimal, delete thousand comma
			cleaned = strings.ReplaceAll(cleaned, ",", "")
		}
		return cleaned
	}

	// if there is only comma, check if it is decimal or not
	if strings.Contains(cleaned, ",") {
		lastComma := strings.LastIndex(cleaned, ",")
		afterSep := len(cleaned) - lastComma - 1
		if afterSep == 2 || afterSep == 1 {
			// comma as decimal
			cleaned = strings.ReplaceAll(cleaned, ".", "")
			cleaned = strings.ReplaceAll(cleaned, ",", ".")
		} else {
			// comma as thousand
			cleaned = strings.ReplaceAll(cleaned, ",", "")
		}
		return cleaned
	}

	// if there is only point, check if it is decimal or not
	if strings.Contains(cleaned, ".") {
		lastDot := strings.LastIndex(cleaned, ".")
		afterSep := len(cleaned) - lastDot - 1
		if afterSep == 2 || afterSep == 1 {
			// point as decimal
			// let it be
		} else {
			// point as thousand
			cleaned = strings.ReplaceAll(cleaned, ".", "")
		}
		return cleaned
	}

	// if there is no point/comma, return it
	return cleaned
}
