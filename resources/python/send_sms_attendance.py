import serial
import sys
import json

def check_connection(ser):
    ser.write(b'AT\r')
    response = ser.read(100).decode()
    return "OK" in response

def send_sms_attendance(phone_number, message):
    try:
        # Establish serial connection
        ser = serial.Serial('COM3', 9600, timeout=5)  # Adjust the port as needed

        # Check connection to modem
        if not check_connection(ser):
            ser.close()
            return {"success": False, "error": "Modem not responding"}
        
        ser.write(b'AT+CMGF=1\r')
        response = ser.read(100).decode()
        if "OK" not in response:
            ser.close()
            return {"success": False, "error": "Failed to set text mode"}

        ser.write(f'AT+CMGS="{phone_number}"\r'.encode())
        response = ser.read(100).decode()
        if ">" not in response:
            ser.close()
            return {"success": False, "error": "No '>' prompt after CMGS"}

        ser.write(message.encode() + b'\r')
        ser.write(bytes([26]))  # Ctrl+Z
        final_response = ser.read(200).decode()
        ser.close()

        if "+CMGS" in final_response and "OK" in final_response:
            return {"success": True, "response": final_response.strip()}
        else:
            return {"success": False, "error": "SMS may not have been sent", "response": final_response.strip()}

    except Exception as e:
        return {"success": False, "error": str(e)}

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(json.dumps({"success": False, "error": "Usage: python send_sms_attendance.py <phone_number> <message>"}))
    else:
        phone_number = sys.argv[1]
        raw_message = sys.argv[2]
        message = raw_message.replace("_", " ")
        result = send_sms_attendance(phone_number, message)
        print(json.dumps(result))
