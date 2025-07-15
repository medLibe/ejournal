package models

type LedgerPreviewRow struct {
	TransactionDate string  `json:"transaction_date"` // ISO string
	Periode         string  `json:"periode"`          // YYYYMM
	Reference       string  `json:"reference"`
	ReferenceNo     string  `json:"reference_no"`
	Department      string  `json:"department"`
	AccountID       uint    `json:"account_id"`
	AccountCode     string  `json:"account_code"`
	AccountName     string  `json:"account_name"`
	Amount          float64 `json:"amount"`
	TransactionType int     `json:"transaction_type"` // 1: debit, 2: kredit
	Description     string  `json:"description"`
}
