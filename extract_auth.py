with open('index.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = 1398 # 1399 is index 1398
end_idx = 1698   # 1698 is index 1697, up to 1698

html_content = lines[start_idx:end_idx]

# Let's extract the HTML out of the template literal and make it a real HTML block
# The JS string is from line 1402 to 1496
html_string = ""
html_string += '<div class="auth-overlay" id="signup-overlay" style="display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.6); backdrop-filter:blur(8px); justify-content:center; align-items:center; opacity:0; transition:opacity 0.3s ease;">\n'

# Get the inner HTML lines (1403 to 1496)
inner_html = ''.join(lines[1403:1497])
html_string += inner_html
html_string += '</div>\n\n<script>\n'

# The rest of the JS functions:
js_lines = lines[1499:1698]
html_string += ''.join(js_lines)
html_string += '</script>\n'

with open('includes/modals/auth.php', 'w', encoding='utf-8') as f:
    f.write(html_string)

new_lines = lines[:start_idx] + ["    <?php include 'includes/modals/auth.php'; ?>\n"] + lines[end_idx:]

with open('index.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
print('Auth modal extracted')
