
import hashlib

password_asli = "praktikum123"
hash_target = hashlib.sha256(password_asli.encode()).hexdigest()

kamus = ["admin", "password", "123456", "praktikum123", "ubuntu"]

print("=== PRAKTIKUM DICTIONARY ATTACK ===")
print("Hash target:", hash_target)

for kata in kamus:
    print("Mencoba:", kata)
    if hashlib.sha256(kata.encode()).hexdigest() == hash_target:
        print("BERHASIL! Password ditemukan:", kata)
        break


import hashlib
import itertools

password_asli = "1234"
hash_target = hashlib.sha256(password_asli.encode()).hexdigest()

print("=== PRAKTIKUM BRUTE FORCE ===")
print("Hash target:", hash_target)

jumlah = 0
for kombinasi in itertools.product("0123456789", repeat=4):
    kandidat = "".join(kombinasi)
    jumlah += 1

    if hashlib.sha256(kandidat.encode()).hexdigest() == hash_target:
        print("BERHASIL! Password ditemukan:", kandidat)
        print("Jumlah percobaan:", jumlah)
        break


import ctypes
import ctypes.util

target = "$1$mERr$gc0bTUXHJaVERV37j2m5D0"
kandidat = [
    "admin", "password", "123456",
    "12345678", "praktikum123", "cisco"
]

library = ctypes.util.find_library("System")
if not library:
    raise RuntimeError("Library sistem tidak ditemukan")

lib = ctypes.CDLL(library)
lib.crypt.argtypes = [ctypes.c_char_p, ctypes.c_char_p]
lib.crypt.restype = ctypes.c_char_p

print("=== CRACKING CISCO TYPE 5 ===")
print("Hash target:", target)

ditemukan = False

for kata in kandidat:
    hasil = lib.crypt(kata.encode(), target.encode())
    if hasil is None:
        raise RuntimeError(
            "MD5-crypt tidak didukung oleh sistem ini"
        )

    print("Mencoba:", kata)

    if hasil.decode() == target:
        print("BERHASIL! Password:", kata)
        ditemukan = True
        break

if not ditemukan:
    print("Password tidak ditemukan dalam kamus.")

