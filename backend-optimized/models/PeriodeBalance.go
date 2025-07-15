package models

import "time"

type PeriodeBalance struct {
	ID             uint      `gorm:"primaryKey"`
	AccountID      uint      `gorm:"column:account_id"`
	Periode        string    `gorm:"column:periode"`
	OpeningBalance float64   `gorm:"column:opening_balance"`
	ClosingBalance float64   `gorm:"column:closing_balance"`
	CreatedAt      time.Time `gorm:"column:created_at"`
	UpdatedAt      time.Time `gorm:"column:updated_at"`
}
