import os

with open("agendamento.php", "r", encoding="utf-8") as f:
    c = f.read()
c = c.replace("var(--card-bg)fff", "var(--card-bg)")
c = c.replace("rgba(255, 255, 255, 0.98)", "rgba(0, 0, 0, 0.85)")
c = c.replace("background: #e8f5e9", "background: #1b3320")
c = c.replace("color: #333", "color: var(--text-primary)")
with open("agendamento.php", "w", encoding="utf-8") as f:
    f.write(c)

with open("admin_dashboard.php", "r", encoding="utf-8") as f:
    c = f.read()
c = c.replace("var(--card-bg)fff", "var(--card-bg)")
c = c.replace("rgba(255, 255, 255, 0.98)", "rgba(0, 0, 0, 0.85)")
with open("admin_dashboard.php", "w", encoding="utf-8") as f:
    f.write(c)

with open("checkout.php", "r", encoding="utf-8") as f:
    c = f.read()
c = c.replace("var(--card-bg)fff", "var(--card-bg)")
c = c.replace("rgba(255, 255, 255, 0.98)", "rgba(0, 0, 0, 0.85)")
c = c.replace("background: #e8f5e9", "background: #1b3320")
c = c.replace("color: #333", "color: var(--text-primary)")
with open("checkout.php", "w", encoding="utf-8") as f:
    f.write(c)

with open("barbeiro_dashboard.php", "r", encoding="utf-8") as f:
    c = f.read()
c = c.replace("var(--card-bg)fff", "var(--card-bg)")
c = c.replace("rgba(255, 255, 255, 0.98)", "rgba(0, 0, 0, 0.85)")
with open("barbeiro_dashboard.php", "w", encoding="utf-8") as f:
    f.write(c)

with open("produto.php", "r", encoding="utf-8") as f:
    c = f.read()
c = c.replace("var(--card-bg)fff", "var(--card-bg)")
c = c.replace("rgba(255, 255, 255, 0.98)", "rgba(0, 0, 0, 0.85)")
with open("produto.php", "w", encoding="utf-8") as f:
    f.write(c)

with open("loja.php", "r", encoding="utf-8") as f:
    c = f.read()
c = c.replace("var(--card-bg)fff", "var(--card-bg)")
c = c.replace("rgba(255, 255, 255, 0.98)", "rgba(0, 0, 0, 0.85)")
with open("loja.php", "w", encoding="utf-8") as f:
    f.write(c)

