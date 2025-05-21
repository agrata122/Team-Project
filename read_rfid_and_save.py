import serial
import json
import time

ser = serial.Serial('COM4', 9600)

PRODUCTS = {
    "53842A03": {
        "product_name": "Tomatoes",
        "description": "Fresh tomatoes",
        "price": 2.99,
        "stock": 100,
        "shop_id": 5
    },
    "FE24CF01": {
        "product_name": "Chicken Breast",
        "description": "Fresh chicken meat",
        "price": 8.99,
        "stock": 50,
        "shop_id": 6
    }
}

while True:
    if ser.in_waiting > 0:
        uid = ser.readline().decode('utf-8').strip()
        product_data = PRODUCTS.get(uid, {})

        with open("rfid_scan.json", "w") as f:
            json.dump({
                "rfid": uid,
                "data": product_data
            }, f)

        print(f"Scanned UID: {uid}")
        time.sleep(1)