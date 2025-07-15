package models

type AccountGroup struct {
	ID               uint   `gorm:"primaryKey"`
	AccountGroupName string `gorm:"column:account_group_name"`
	NormalBalance    int    `gorm:"column:normal_balance"` // 1: Debit, 2: Kredit
}
