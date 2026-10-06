import os

with open("agendamento.php", "r", encoding="utf-8") as f:
    c = f.read()

c = c.replace("border-color: var(--primary-color)", "border-color: var(--secondary-color)")
c = c.replace("color: var(--primary-color)", "color: var(--secondary-color)")
c = c.replace("background: var(--primary-color)", "background: var(--secondary-color)")

with open("agendamento.php", "w", encoding="utf-8") as f:
    f.write(c)

