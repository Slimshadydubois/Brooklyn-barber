import os
import re

html_footer = """    <footer>
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; flex-wrap: wrap; gap: 1.5rem;">
            <div style="text-align: left;">
                <div style="margin-bottom: 0.5rem;">
                    <a href="loja.php" style="color: var(--primary-color); text-decoration: none; font-size: 1.1rem; font-weight: bold;">Loja de Produtos</a>
                </div>
                <p>&copy; <?php echo date('Y'); ?> Brooklyn Barbershop. Todos os direitos reservados.</p>
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center; justify-content: center;">
                <span style="font-size: 0.9rem; color: #ccc;">Aceitamos:</span>
                <img src="assets/img/pix-seeklogo (2).png" alt="Pix" style="height: 24px; filter: brightness(0) invert(1);">
                <img src="assets/img/logo_mercado_pago.png" alt="Mercado Pago" style="height: 28px; filter: brightness(0) invert(1);">
            </div>
        </div>
    </footer>"""

for file in os.listdir("."):
    if file.endswith(".php"):
        with open(file, "r", encoding="utf-8") as f:
            content = f.read()
        
        # Regex to find <footer>...</footer>
        new_content = re.sub(r"<footer>.*?</footer>", html_footer, content, flags=re.DOTALL)
        
        if new_content != content:
            with open(file, "w", encoding="utf-8") as f:
                f.write(new_content)
            print("Updated", file)

