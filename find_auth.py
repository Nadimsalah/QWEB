with open('index.php', 'r', encoding='utf-8') as f:
    for i, line in enumerate(f):
        if 'auth' in line.lower() or 'login' in line.lower() or 'register' in line.lower():
            if '<div id="' in line:
                print(f'Line {i+1}: {line.strip()}')
