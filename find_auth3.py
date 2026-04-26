with open('index.php', 'r', encoding='utf-8') as f:
    for i, line in enumerate(f):
        if 'login' in line.lower() or 'signup' in line.lower() or 'register' in line.lower():
            print(f'Line {i+1}: {line.strip()}')
