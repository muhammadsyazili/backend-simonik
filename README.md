# API Spec
---
## Sistem Informasi Monitoring Kinerja dan Resiko

### Login
Request :
- Method : POST
- Endpoint : `/login`
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "username" : "required, string",
    "password" : "required, string"
}
```

Response:
```json
{
    "status" : "boolean",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "indicators" : {
            "partials" : [
                {
                    "indicator": "string",
                    "type": "string",
                    "measure": "string",
                    "polarity": "string|null",
                    "bg_color": {
                        "r": "integer",
                        "g": "integer",
                        "b": "integer"
                    },
                    "achievement": {
                        "value": {
                            "original": "string|float|integer",
                            "showed": "string|float|integer"
                        }
                    },
                    "status": "enum[-, BELUM DINILAI, BAIK, HATI-HATI, MASALAH]",
                    "status_symbol": "enum[+0, -0, +1, 0, -1]",
                    "status_color": "enum[none, info, success, warning, danger]"
                }
            ],
            "total" : {
                "PPK_100": {
                    "value": {
                        "original": "float|integer",
                        "showed": "float|integer"
                    }
                },
                "PPK_110": {
                    "value": {
                        "original": "float|integer",
                        "showed": "float|integer"
                    }
                },
                "PPK_100_status": "enum[MASALAH, HATI-HATI, BAIK]",
                "PPK_100_color_status": "enum[danger, warning, success]",
                "PPK_110_status": "enum[MASALAH, HATI-HATI, BAIK]",
                "PPK_110_color_status": "enum[danger, warning, success]"
            }
        }
    },
    "errors": "null|array of objects"
}
```

### Dashboard
Request :
- Method : GET
- Endpoint : `/dashboard`
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "level" : "required, string",
    "unit" : "required, string",
    "tahun" : "required, string|integer",
    "bulan" : "required, enum[jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dec]"
}
```

Response:
```json
{
    "status" : "boolean",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "indicators" : {
            "partials" : [
                {
                    "indicator": "string",
                    "type": "string",
                    "measure": "string",
                    "polarity": "string|null",
                    "bg_color": {
                        "r": "integer",
                        "g": "integer",
                        "b": "integer"
                    },
                    "achievement": {
                        "value": {
                            "original": "string|float|integer",
                            "showed": "string|float|integer"
                        }
                    },
                    "status": "enum[-, BELUM DINILAI, BAIK, HATI-HATI, MASALAH]",
                    "status_symbol": "enum[+0, -0, +1, 0, -1]",
                    "status_color": "enum[none, info, success, warning, danger]"
                }
            ],
            "total" : {
                "PPK_100": {
                    "value": {
                        "original": "float|integer",
                        "showed": "float|integer"
                    }
                },
                "PPK_110": {
                    "value": {
                        "original": "float|integer",
                        "showed": "float|integer"
                    }
                },
                "PPK_100_status": "enum[MASALAH, HATI-HATI, BAIK]",
                "PPK_100_color_status": "enum[danger, warning, success]",
                "PPK_110_status": "enum[MASALAH, HATI-HATI, BAIK]",
                "PPK_110_color_status": "enum[danger, warning, success]"
            }
        }
    },
    "errors": "null|array of objects"
}
```