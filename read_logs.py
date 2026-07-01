import json
import sys

log_file = r"C:\Users\DELL\.gemini\antigravity\brain\4fd60716-1a11-4902-9830-806231e64c21\.system_generated\logs\transcript.jsonl"

try:
    with open(log_file, "r", encoding="utf-8") as f:
        for line in f:
            try:
                data = json.loads(line)
                if data.get("type") == "USER_INPUT":
                    print(f"USER: {data.get('content')}")
            except:
                pass
except Exception as e:
    print(e)
