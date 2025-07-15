package models

import "time"

type GeneralLedger struct {
	ID              uint      `gorm:"primaryKey"`
	ImportID        uint      `gorm:"column:import_id"`
	TransactionDate time.Time `gorm:"column:transaction_date"`
	Periode         string    `gorm:"column:periode"`
	Reference       string    `gorm:"column:reference"`
	ReferenceNo     string    `gorm:"column:reference_no"`
	Department      string    `gorm:"column:department"`
	AccountID       uint      `gorm:"column:account_id"`
	Amount          float64   `gorm:"column:amount"`
	TransactionType int       `gorm:"column:transaction_type"`
	Description     string    `gorm:"column:description"`
	CreatedAt       time.Time `gorm:"column:created_at"`
	UpdatedAt       time.Time `gorm:"column:updated_at"`
}

type LedgerRow struct {
	Tanggal       string  `json:"tanggal"`
	NomorAkun     string  `json:"nomor_akun"`
	NamaAkun      string  `json:"nama_akun"`
	Department    string  `json:"department"`
	NomorSumber   string  `json:"nomor_sumber"`
	TipeSumber    string  `json:"tipe_sumber"`
	Nominal       float64 `json:"nominal"`
	Keterangan    string  `json:"keterangan"`
	TipeTransaksi int     `json:"tipe_transaksi"`
	IDAkun        uint    `json:"id_akun"`
}

type ValidatedLedgerRow struct {
	Tanggal        string
	TipeSumber     string
	NomorSumber    string
	Department     string
	NomorAkun      string
	IDAkun         uint
	Amount         float64
	TipeTransaksi  int
	Keterangan     string
	NormalBalance  int
	OpeningBalance float64
	Periode        string
}
