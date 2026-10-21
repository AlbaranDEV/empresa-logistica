FROM node:22-alpine

WORKDIR /app

# Las dependencias se instalan en el arranque si node_modules (volumen) está vacío
CMD ["sh", "-c", "[ -d node_modules/@angular ] || npm install; npx ng serve --host 0.0.0.0 --port 4200 --poll 2000"]
