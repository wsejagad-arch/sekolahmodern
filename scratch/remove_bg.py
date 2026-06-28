import sys
import subprocess
try:
    from rembg import remove
    from PIL import Image
except ImportError:
    subprocess.check_call([sys.executable, "-m", "pip", "install", "rembg[cpu]", "Pillow"])
    from rembg import remove
    from PIL import Image

input_path = r"c:\xampp\htdocs\jurnal\img\hero_students.png"
output_path = r"c:\xampp\htdocs\jurnal\img\hero_students_transparent.png"

print("Removing background...")
input_image = Image.open(input_path)
output_image = remove(input_image)
output_image.save(output_path)
print("Done.")
