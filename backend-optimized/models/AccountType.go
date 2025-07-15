package models

type AccountType struct {
	ID              uint         `gorm:"primaryKey"`
	AccountGroupID  uint         `gorm:"column:account_group_id"`
	AccountGroup    AccountGroup `gorm:"foreignKey:AccountGroupID"`
	AccountTypeName string       `gorm:"column:account_type_name"`
}
