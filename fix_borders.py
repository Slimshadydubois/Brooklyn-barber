import os
import re

for file in os.listdir("."):
    if file.endswith(".php"):
        with open(file, "r", encoding="utf-8") as f:
            content = f.read()
        
        new_content = content.replace("border: 1px solid #ccc", "border: 1px solid #444")
        new_content = new_content.replace("border: 1px solid #ddd", "border: 1px solid #444")
        new_content = new_content.replace("border-right: 2px dashed #eee", "border-right: 2px dashed #444")
        new_content = new_content.replace("border-bottom: 2px dashed #eee", "border-bottom: 2px dashed #444")
        
        if new_content != content:
            with open(file, "w", encoding="utf-8") as f:
                f.write(new_content)

