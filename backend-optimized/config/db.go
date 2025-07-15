package config

import (
	"fmt"

	"gorm.io/driver/mysql"
	"gorm.io/gorm"
	"gorm.io/gorm/logger"
)

var allowedDBs = map[string]bool{
	"ejournal_mac":  true,
	"ejournal_ms":   true,
	"ejournal_tkp":  true,
	"ejournal_eps":  true,
	"ejournal_diko": true,
	"ejournal_kis":  true,
	"ejournal_cpp":  true,
	"ejournal_tmc":  true,
}

func InitDBWithName(dbName string) (*gorm.DB, error) {
	if !allowedDBs[dbName] {
		return nil, fmt.Errorf("database tidak diizinkan")
	}

	dsn := fmt.Sprintf("root:%s@tcp(127.0.0.1:3306)/%s?charset=utf8mb4&parseTime=True&loc=Local",
		"arang123", dbName)
	return gorm.Open(mysql.Open(dsn), &gorm.Config{
		Logger: logger.Default.LogMode(logger.Silent),
	})
}
