file = "frontend/pages/Teacher Dashboard/profile-dashboard.php"
with open(file, "r", encoding="utf-8") as f:
    content = f.read()

old = '''<!-- Hidden input for instructorID (manually enterable) -->
                        <label for="instructorID"></label>
                        <input type="text" id="instructorID" name="instructorID" required>'''

new = '<!-- instructorID now comes from $_SESSION[\'user_id\'] server-side -->'

if old in content:
    content = content.replace(old, new)
    print("Replaced")
else:
    print("Pattern not found - check current content, whitespace may differ")

with open(file, "w", encoding="utf-8") as f:
    f.write(content)
