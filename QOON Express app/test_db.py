import mysql.connector

try:
    conn = mysql.connector.connect(
        host="145.223.33.118",
        user="qoon_Qoon",
        password=";)xo6b(RE}K%",
        database="qoon_Qoon",
        connect_timeout=5
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT DriverID, DriverPhone, DriverPassword FROM Drivers LIMIT 5")
    for row in cursor.fetchall():
        print(row)
except Exception as e:
    print("Error:", e)
