package models

type Account struct {
	ID              uint        `gorm:"primaryKey"`
	AccountName     string      `gorm:"column:account_name"`
	AccountTypeID   uint        `gorm:"column:account_type_id"`
	AccountType     AccountType `gorm:"foreignKey:AccountTypeID"`
	AccountCode     string      `gorm:"column:account_code"`
	AccountCodeAlt  string      `gorm:"column:account_code_alt"`
	AccountNameAlt  string      `gorm:"column:account_name_alt"`
	ParentID        *string     `gorm:"column:parent_id"` // Nullable parent account
	OpeningBalance  float64     `gorm:"column:opening_balance"`
	PreviousBalance float64     `gorm:"column:previous_balance"`
	IsActive        bool        `gorm:"column:is_active"`
}
