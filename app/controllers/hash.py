import bcrypt

# Contraseña original
password = b"EMP-2025-001CvazquezSA2"

# Generar un hash (equivalente a password_hash de PHP con BCRYPT)
hashed = bcrypt.hashpw(password, bcrypt.gensalt(rounds=10))

print(hashed.decode())  # Devuelve algo como: $2b$10$...
