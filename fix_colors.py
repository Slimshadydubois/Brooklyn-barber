import re

with open("assets/css/style.css", "r", encoding="utf-8") as f:
    c = f.read()

# Make .btn-primary red
c = re.sub(r"(\.btn-primary\s*{[^}]*background:\s*)var\(--primary-color\)", r"\1var(--secondary-color)", c)

# Make .btn-primary:hover use a darker red or primary
c = re.sub(r"(\.btn-primary:hover\s*{[^}]*background:\s*)var\(--secondary-color\)", r"\1#b71c1c", c)

# Make section-title underline red
c = re.sub(r"(\.section-title::after\s*{[^}]*background:\s*)var\(--primary-color\)", r"\1var(--secondary-color)", c)

# Make service-card hover border red
c = re.sub(r"(\.service-card:hover\s*{[^}]*border:\s*2px solid\s*)var\(--primary-color\)", r"\1var(--secondary-color)", c)

# Make carousel-control red
c = re.sub(r"(\.carousel-control\s*{[^}]*border:\s*1px solid\s*)var\(--primary-color\)", r"\1var(--secondary-color)", c)
c = re.sub(r"(\.carousel-control\s*{[^}]*color:\s*)var\(--primary-color\)", r"\1var(--secondary-color)", c)
c = re.sub(r"(\.carousel-control:hover\s*{[^}]*background:\s*)var\(--primary-color\)", r"\1var(--secondary-color)", c)

with open("assets/css/style.css", "w", encoding="utf-8") as f:
    f.write(c)

