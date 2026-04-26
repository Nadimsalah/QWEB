with open('index.php', 'r', encoding='utf-8') as f:
    for i, line in enumerate(f):
        if 'query(' in line or 'mysqli_query' in line:
            print(f'Line {i+1}: {line.strip()}')
