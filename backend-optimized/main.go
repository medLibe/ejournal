package main

import (
	"backend-optimized/controllers"
	"log"

	"github.com/gin-gonic/gin"
)

func main() {
	router := gin.Default()

	router.POST("/api/import-ledger", controllers.ImportGeneralLedgerHandler)
	router.POST("/api/store-ledger", controllers.StoreImportGeneralLedgerHandler)
	log.Println("Go service running on :8081")
	router.Run(":8081")
}
