package models

import "time"

type GeneralLedgerImport struct {
	ID         uint      `gorm:"primaryKey"`
	ImportDate time.Time `gorm:"column:import_date"`
	ImportNo   string    `gorm:"column:import_no"`
	CreatedBy  string    `gorm:"column:created_by"`
	UpdatedBy  string    `gorm:"column:updated_by"`
	CreatedAt  time.Time `gorm:"column:created_at"`
	UpdatedAt  time.Time `gorm:"column:updated_at"`
}
