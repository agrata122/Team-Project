
import serial
import time
import webbrowser
import re

SERIAL_PORT = 'COM4' 
BAUDRATE = 9600
URL_TEMPLATE = 'http://localhost/GCO/frontend/user/product_detail.php?rfid={}'

UID_PATTERN = re.compile(r'^[A-F0-9]{8}$')

def read_rfid():
    try:
        ser = serial.Serial(
            port=SERIAL_PORT,
            baudrate=BAUDRATE,
            timeout=1,
            write_timeout=1,
            inter_byte_timeout=1
        )

        while True:
            try:
                if ser.in_waiting:
                    data = ser.readline().decode('utf-8').strip()

                    if UID_PATTERN.match(data):
                        url = URL_TEMPLATE.format(data)
                        webbrowser.open(url)
                        time.sleep(3)

            except serial.SerialException:
                break
            except UnicodeDecodeError:
                continue

            time.sleep(0.1)

    except serial.SerialException:
        pass
    except KeyboardInterrupt:
        pass
    finally:
        if 'ser' in locals():
            ser.close()

if __name__ == '__main__':
    read_rfid()
