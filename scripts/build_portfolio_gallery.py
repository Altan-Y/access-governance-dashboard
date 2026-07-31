from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

OUT = Path('screenshots')
NAVY = '#07182b'
PANEL = '#0d2946'
WHITE = '#f7f7fb'
MUTED = '#b8c6d8'


def font(size: int, bold: bool = False):
    name = 'DejaVuSans-Bold.ttf' if bold else 'DejaVuSans.ttf'
    path = Path('/usr/share/fonts/truetype/dejavu') / name
    return ImageFont.truetype(str(path), size)


def fit(image: Image.Image, size: tuple[int, int]) -> Image.Image:
    target_w, target_h = size
    ratio = max(target_w / image.width, target_h / image.height)
    resized = image.resize((round(image.width * ratio), round(image.height * ratio)), Image.Resampling.LANCZOS)
    left = max(0, (resized.width - target_w) // 2)
    top = max(0, (resized.height - target_h) // 2)
    return resized.crop((left, top, left + target_w, top + target_h))


def framed(canvas, image, box, label):
    draw = ImageDraw.Draw(canvas)
    x, y, w, h = box
    draw.rounded_rectangle((x, y, x + w, y + h), radius=18, fill=PANEL, outline=WHITE, width=2)
    draw.text((x + 18, y + 13), label, fill=WHITE, font=font(22))
    inner = (x + 2, y + 50, w - 4, h - 52)
    picture = fit(image, (inner[2], inner[3]))
    canvas.paste(picture, (inner[0], inner[1]))


login = Image.open(OUT / 'accesshub-login.png').convert('RGB')
dashboard = Image.open(OUT / 'accesshub-dashboard.png').convert('RGB')
roles = Image.open(OUT / 'accesshub-job-roles.png').convert('RGB')

for source, destination in [
    (login, 'accesshub-login.webp'),
    (dashboard, 'accesshub-dashboard.webp'),
    (roles, 'accesshub-job-roles.webp'),
]:
    source.save(OUT / destination, 'WEBP', quality=82, method=6)

canvas = Image.new('RGB', (1600, 1180), NAVY)
draw = ImageDraw.Draw(canvas)
draw.text((64, 42), 'AccessHub — Product Gallery', fill=WHITE, font=font(38, True))
draw.text((64, 93), 'Login, dashboard and permission-aware job-role editing', fill=MUTED, font=font(21))

framed(canvas, dashboard, (64, 142, 1472, 620), 'Dashboard overview')
framed(canvas, login, (64, 806, 700, 310), 'Demo login')
framed(canvas, roles, (800, 806, 736, 310), 'Job roles and editing permissions')

canvas.save(OUT / 'accesshub-gallery.webp', 'WEBP', quality=82, method=6)

for png in OUT.glob('accesshub-*.png'):
    png.unlink()
