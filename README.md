# API Spec
---
## Sistem Informasi Monitoring Kinerja dan Resiko
##### Keterangan Atribut:
- id : identitas unik **Laporan Peta Pohon**.
- territory_id : identitas unik dari **Unit Induk Wilayah (UIW)**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- area_id : identitas unik dari **Unit Pelaksana Pelayanan Pelanggan (UP3)**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- rayon_id : identitas unik dari **Unit Layanan Pelanggan (ULP)**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- feeder_id : identitas unik dari **Penyulang**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- map_tree_id : identitas unik dari _endpoint_ `/peta-pohon`, sumber _endpoint_ dari aplikasi **PETA POHON**.
- tree : jenis pohon.
- tree_num : nomor peta pohon.
- workers : pekerja-pekerja rabas.
- date_exe_from : tanggal ROW dari.
- date_exe_to : tanggal ROW sampai.
- dist_before_exe : jarak aman peta pohon sebelum dilakukan ROW, **satuan sentimeter**.
- dist_after_exe : jarak aman peta pohon setelah dilakukan ROW, **satuan sentimeter**.
- date_exe : tanggal ROW.
- img_path_exe : path evidence ROW.
- created_by : identitas unik dari user pembuat data, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- updated_by : identitas unik dari user pengubah data, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- created_at : tanggal create data.
- updated_at : tanggal update data.

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
    }
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
    "errors": "null|arasa"
}
```

### Create - Laporan Peta Pohon
Request :
- Method : POST
- Endpoint : `/laporan-peta-pohon`
- Authentication : API key
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "map_tree_id" : "integer, required",
    "workers" : "string, maximal-length=255",
    "dist_before_exe" : "float, required",
    "dist_after_exe" : "float, required",
    "date_exe" : "string, required, format=yyyy-mm-dd",
    "img_path_exe" : "string, required",
    "created_by" : "string, required"
}
```

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "map_tree_id" : "integer",
        "workers" : "string",
        "dist_before_exe" : "float",
        "dist_after_exe" : "float",
        "date_exe" : "string",
        "img_path_exe" : "string",
        "created_by" : "string",
        "updated_by" : "string|null",
        "created_at" : "string",
        "updated_at" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Get - Laporan Peta Pohon
Request :
- Method : GET
- Endpoint : `/laporan-peta-pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "rayon_id" : "string",
        "feeder_id" : "string",
        "tree" : "string",
        "tree_num" : "integer",
        "workers" : "string",
        "dist_before_exe" : "float",
        "dist_after_exe" : "float",
        "state_before_exe" : "string",
        "state_after_exe" : "string",
        "date_exe" : "string",
        "img_path_exe" : "string",
        "created_by" : "string",
        "updated_by" : "string|null",
        "created_at" : "string",
        "updated_at" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Update - Laporan Peta Pohon
Request :
- Method : PUT
- Endpoint : `/laporan-peta-pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "map_tree_id" : "integer, required",
    "workers" : "string, maximal-length=255",
    "dist_before_exe" : "float, required",
    "dist_after_exe" : "float, required",
    "date_exe" : "string, required, format=yyyy-mm-dd",
    "img_path_exe" : "string, required",
    "updated_by" : "string, required"
}
```

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "map_tree_id" : "integer",
        "workers" : "string",
        "dist_before_exe" : "float",
        "dist_after_exe" : "float",
        "date_exe" : "string",
        "img_path_exe" : "string",
        "created_by" : "string",
        "updated_by" : "string|null",
        "created_at" : "string",
        "updated_at" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### List - Laporan Peta Pohon
Request :
- Method : GET
- Endpoint : `/laporan-peta-pohon`
- Allowed Params :
    - territory_id : string, default=all
    - area_id : string, default=all
    - rayon_id : string, default=all
    - feeder_id : string, default=all
    - map_tree_id : integer, greater-than=0, default=all
    - date_exe_from : string, format=yyyy-mm-dd, default=early_entry
    - date_exe_to : string, format=yyyy-mm-dd, default=last_entry
    - state_before_exe : string, default=all, options=aman,waspada,bahaya
    - state_after_exe : string, default=all, options=aman,waspada,bahaya
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : [
        {
            "rayon_id" : "string",
            "feeder_id" : "string",
            "tree" : "string",
            "tree_num" : "integer",
            "workers" : "string",
            "dist_before_exe" : "float",
            "dist_after_exe" : "float",
            "state_before_exe" : "string",
            "state_after_exe" : "string",
            "date_exe" : "string",
            "img_path_exe" : "string",
            "created_by" : "string",
            "updated_by" : "string|null",
            "created_at" : "string",
            "updated_at" : "string"
        }
    ]
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Export - Laporan Peta Pohon
Request : equal to **List - Laporan Peta Pohon**.

Response Success: equal to **List - Laporan Peta Pohon**.

Response Error : equal to **List - Laporan Peta Pohon**.

### Delete - Laporan Peta Pohon
Request :
- Method : DELETE
- Endpoint : `/laporan-peta-pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string"
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

## Peta Pohon
##### Keterangan Atribut:
- id : identitas unik **Peta Pohon**.
- territory_id : identitas unik dari **Unit Induk Wilayah (UIW)**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- area_id : identitas unik dari **Unit Pelaksana Pelayanan Pelanggan (UP3)**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- rayon_id : identitas unik dari **Unit Layanan Pelanggan (ULP)**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- feeder_id : identitas unik dari **Penyulang**, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- tree_id : identitas unik dari endpoint `/pohon`, sumber endpoint dari aplikasi **PETA POHON**.
- tree_num : nomor peta pohon, **nomor harus _uniq_ jika pada satu penyulang yang sama, tapi nomor boleh _un-uniq_ jika penyulang yang berbeda**.
- tree : jenis pohon.
- loc : lokasi peta pohon.
- lat : koordinat latitude peta pohon.
- long : koordinat longitude peta pohon.
- dist_last_exe : jarak aman terakhir ROW peta pohon, **satuan sentimeter**, jika peta pohon tidak mempunyai riwayat laporan ROW maka secara otomatis jarak aman akan di set **0 cm**.
- date_last_exe : tanggal terakhir ROW peta pohon, jika peta pohon tidak mempunyai riwayat laporan ROW maka secara otomatis tanggal akan di set **2021-01-01**.
- state_last_exe : status terakhir ROW peta pohon, **kategori status = aman (> 300 cm), waspada (> 100 cm s.d < 300 cm), aman (< 100 cm)**, jika peta pohon tidak mempunyai riwayat laporan ROW maka secara otomatis status akan di set **bahaya**.
- img_path_last_exe : tautan foto terakhir ROW peta pohon, jika peta pohon tidak mempunyai riwayat laporan ROW maka secara otomatis tautan foto akan di set **{img_path}/default.jpg**.
- dist_now : jarak aman peta pohon saat ini, **satuan sentimeter**.
- state_now : status peta pohon saat ini.
- date_next_exe : tanggal prediksi selanjutnya untuk ROW peta pohon.
- created_by : identitas unik dari user pembuat data, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- updated_by : identitas unik dari user pengubah data, sumber _endpoint_ dari aplikasi **ASIIK ULTRA**.
- created_at : tanggal create data peta pohon.
- updated_at : tanggal update data peta pohon.

### Create - Peta Pohon
Request :
- Method : POST
- Endpoint : `/peta-pohon`
- Authentication : API key
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "territory_id" : "string, required",
    "area_id" : "string, required",
    "rayon_id" : "string, required",
    "feeder_id" : "string, required",
    "tree_id" : "integer, greater-than=0, required",
    "tree_num" : "integer, greater-than=0, required",
    "loc" : "string, maximal-length=255",
    "lat" : "double, required, minimal-value=-90, maximal-value=90, precision-length=15",
    "long" : "double, required, minimal-value=-180, maximal-value=180, precision-length=14",
    "created_by" : "string, required"
}
```

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "territory_id" : "string",
        "area_id" : "string",
        "rayon_id" : "string",
        "feeder_id" : "string",
        "tree_id" : "integer",
        "tree_num" : "integer",
        "loc" : "string",
        "lat" : "double",
        "long" : "double",
        "created_by" : "string",
        "updated_by" : "string|null",
        "created_at" : "string",
        "updated_at" : "string",
        "tree" : "string",
        "dist_last_exe" : "float",
        "date_last_exe" : "string",
        "state_last_exe" : "string",
        "img_path_last_exe" : "string",
        "dist_now" : "float",
        "state_now" : "string",
        "date_next_exe" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Get - Peta Pohon
Request :
- Method : GET
- Endpoint : `/peta-pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "territory_id" : "string",
        "area_id" : "string",
        "rayon_id" : "string",
        "feeder_id" : "string",
        "tree_id" : "integer",
        "tree_num" : "integer",
        "loc" : "string",
        "lat" : "double",
        "long" : "double",
        "created_by" : "string",
        "updated_by" : "string|null",
        "created_at" : "string",
        "updated_at" : "string",
        "tree" : "string",
        "dist_last_exe" : "float",
        "date_last_exe" : "string",
        "state_last_exe" : "string",
        "img_path_last_exe" : "string",
        "dist_now" : "float",
        "state_now" : "string",
        "date_next_exe" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Update - Peta Pohon
Request :
- Method : PUT
- Endpoint : `/peta-pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "territory_id" : "string, required",
    "area_id" : "string, required",
    "rayon_id" : "string, required",
    "feeder_id" : "string, required",
    "tree_id" : "integer, greater-than=0, required",
    "tree_num" : "integer, greater-than=0, required",
    "loc" : "string, maximal-length=255",
    "lat" : "double, required, minimal-value=-90, maximal-value=90, precision-length=15",
    "long" : "double, required, minimal-value=-180, maximal-value=180, precision-length=14",
    "updated_by" : "string, required"
}
```

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "territory_id" : "string",
        "area_id" : "string",
        "rayon_id" : "string",
        "feeder_id" : "string",
        "tree_id" : "integer",
        "tree_num" : "integer",
        "loc" : "string",
        "lat" : "double",
        "long" : "double",
        "created_by" : "string",
        "updated_by" : "string|null",
        "created_at" : "string",
        "updated_at" : "string",
        "tree" : "string",
        "dist_last_exe" : "float",
        "date_last_exe" : "string",
        "state_last_exe" : "string",
        "img_path_last_exe" : "string",
        "dist_now" : "float",
        "state_now" : "string",
        "date_next_exe" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### List - Peta Pohon
Request :
- Method : GET
- Endpoint : `/peta-pohon`
- Allowed Params :
    - territory_id : string, default=all
    - area_id : string, default=all
    - rayon_id : string, default=all
    - feeder_id : string, default=all
    - tree_id : integer, greater-than=0, default=all
    - state : string, default=all, options=aman,waspada,bahaya
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : [
        {
            "id" : "integer",
            "territory_id" : "string",
            "area_id" : "string",
            "rayon_id" : "string",
            "feeder_id" : "string",
            "tree_id" : "integer",
            "tree_num" : "integer",
            "loc" : "string",
            "lat" : "double",
            "long" : "double",
            "created_by" : "string",
            "updated_by" : "string|null",
            "created_at" : "string",
            "updated_at" : "string",
            "tree" : "string",
            "dist_last_exe" : "float",
            "date_last_exe" : "string",
            "state_last_exe" : "string",
            "img_path_last_exe" : "string",
            "dist_now" : "float",
            "state_now" : "string",
            "date_next_exe" : "string"
        }
    ]
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Export - Peta Pohon
Request : equal to **List - Peta Pohon**.

Response Success: equal to **List - Peta Pohon**.

Response Error : equal to **List - Peta Pohon**.

### Delete - Peta Pohon
Request :
- Method : DELETE
- Endpoint : `/peta-pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string"
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

## Pohon
##### Keterangan Atribut:
- id : identitas unik **Jenis Pohon**.
- tree : nama pohon.
- growth : koefisien pertumbuhan pohon per hari, **satuan sentimeter**.
- created_at : tanggal create data pohon.
- updated_at : tanggal update data pohon.

### Create - Pohon
Request :
- Method : POST
- Endpoint : `/pohon`
- Authentication : API key
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "tree" : "string, required, unique, maximal-length=100",
    "growth" : "float, required, greater-than=0"
}
```

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "tree" : "string",
        "growth" : "float",
        "created_at" : "string",
        "updated_at" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Get - Pohon
Request :
- Method : GET
- Endpoint : `/pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "tree" : "string",
        "growth" : "float",
        "created_at" : "string",
        "updated_at" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Update - Pohon
Request :
- Method : PUT
- Endpoint : `/pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json
    - Accept : application/json
- Body :
```json
{
    "tree" : "string, required, unique, maximal-length=100",
    "growth" : "float, required, greater-than=0"
}
```

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : {
        "id" : "integer",
        "tree" : "string",
        "growth" : "float",
        "created_at" : "string",
        "updated_at" : "string"
    }
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### List - Pohon
Request :
- Method : GET
- Endpoint : `/pohon`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success :
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : [
        {
            "id" : "integer",
            "tree" : "string",
            "growth" : "float",
            "created_at" : "string",
            "updated_at" : "string"
        }
    ]
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```

### Delete - Pohon
Request :
- Method : DELETE
- Endpoint : `/pohon/{id}`
- Authentication : API key
- Header : 
    - Content-Type : application/json

Response Success:
```json
{
    "status" : "boolean:true",
    "code" : "integer",
    "message" : "string",
    "data" : "null"
}
```

Response Error :
```json
{
    "status" : "boolean:false",
    "code" : "integer",
    "message" : "string",
    "errors" : "null|array(only-code:422)"
}
```