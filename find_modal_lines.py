with open('index.php', 'r', encoding='utf-8') as f:
    for i, line in enumerate(f):
        if '<div id="teleport-modal-overlay"' in line or '<div id="comments-modal-overlay"' in line or '<div id="signup-overlay"' in line or '<div id="pm-overlay"' in line or '<div id="share-modal-overlay"' in line or '<div id="checkout-modal"' in line:
            print(f'Line {i+1}: {line.strip()}')
