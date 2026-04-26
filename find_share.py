with open('index.php', 'r', encoding='utf-8') as f:
    for i, line in enumerate(f):
        if 'id="share-modal-overlay"' in line:
            print(f'Line {i+1}: {line.strip()}')
