import re

with open('index.php', 'r', encoding='utf-8') as f:
    idx = f.read()

styles = re.findall(r'<style>(.*?)</style>', idx, re.DOTALL)
css_content = '\n'.join(styles)

new_idx = re.sub(r'<style>.*?</style>', '', idx, flags=re.DOTALL)

with open('assets/css/main.css', 'w', encoding='utf-8') as f:
    f.write(css_content)

new_idx = new_idx.replace('</head>', '    <link rel="stylesheet" href="assets/css/main.css">\n</head>')

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(new_idx)

print('CSS Extracted')
