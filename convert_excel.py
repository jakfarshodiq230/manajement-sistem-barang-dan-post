import pandas as pd
import json

files = {
    "diesel": r"D:\SHODIQ SOLUTIN\PT.DUMAI\Manajement Barang\DATA DIESEL-CLAUDE 3.xlsx",
    "sparepart": r"D:\SHODIQ SOLUTIN\PT.DUMAI\Manajement Barang\Data Sparepart Mobil - Pagaruyung Diesel.xlsx",
    "unit": r"D:\SHODIQ SOLUTIN\PT.DUMAI\Manajement Barang\DATA UNIT-CLAUDE 1.xlsx"
}

for key, path in files.items():
    try:
        df = pd.read_excel(path)
        for col in df.select_dtypes(include=["datetime64"]).columns:
            df[col] = df[col].dt.strftime("%Y-%m-%d")
        
        json_str = df.to_json(orient="records", date_format="iso")
        with open(f"{key}.json", "w", encoding="utf-8") as f:
            f.write(json_str)
        print(f"Converted {key} - done")
    except Exception as e:
        print(f"Error on {key}: {e}")

