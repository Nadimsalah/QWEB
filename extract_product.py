with open('index.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = 1674 
end_idx = 2054

with open('includes/modals/product.php', 'w', encoding='utf-8') as f:
    f.writelines(lines[start_idx:end_idx])

new_lines = lines[:start_idx] + ["    <?php include 'includes/modals/product.php'; ?>\n"] + lines[end_idx:]

with open('index.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
print('Product extracted')
