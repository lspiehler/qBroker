var spec = {
	"openapi": "3.0.0",
	"info": {
	   "description": "This is an API for requesting print mappings from qBroker",
	   "version": "1.0.0",
	   "title": "qBroker",
	   "termsOfService": "http://swagger.io/terms/",
	   "contact": {
		  "email": "lyas.spiehler@sapphirehealth.org"
	   },
	   "license": {
		  "name": "Apache 2.0",
		  "url": "http://www.apache.org/licenses/LICENSE-2.0.html"
	   }
	},
	"tags": [
	   {
		  "name": "mappings",
		  "description": "Request print mappings",
		  "externalDocs": {
			 "description": "Find out more",
			 "url": "https://github.com/lspiehler/qBroker.git"
		  }
	   }
	],
	"paths": {
		"/activeservers": {
			"get": {
			   "tags": [
				  "activeservers"
			   ],
			   "summary": "Request active servers",
			   "description": "",
			   "responses": {
				"200": {
					"description": "OK",
					"content": {
						"application/json": {
							"schema": {
							   "type": "string"
							}
						 },
						 "application/xml": {
							"schema": {
							   "type": "string"
							}
						 }
					}
				 }
			 }
			}
		},
	   "/mappings/{computername}/{username}": {
		  "get": {
			 "tags": [
				"mappings"
			 ],
			 "summary": "Request print mappings",
			 "description": "",
			 "parameters": [
				{
					"name": "computername",
					"in": "path",
					"description": "The computer name the mappings are being requested for",
					"required": true,
					"schema": {
						"type": "string",
						"default": "DESKTOP-HS0AL31"
					}
				},
				{
					"name": "username",
					"in": "path",
					"description": "The user name the mappings are being requested for",
					"required": true,
					"schema": {
						"type": "string",
						"default": "LYAS.SPIEHLER"
					}
				}
			],
			 "responses": {
				"200": {
					"description": "OK",
					"content": {
						"application/json": {
							"schema": {
							   "type": "string"
							}
						 },
						 "application/xml": {
							"schema": {
							   "type": "string"
							}
						 }
					}
				 }
			 }
		  }
	   }
	},
	"externalDocs": {
	   "description": "Find out more about Swagger",
	   "url": "http://swagger.io"
	},
	"servers": [
	   {
		  "url": "/api"
	   }
	]
 }