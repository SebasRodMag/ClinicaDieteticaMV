# --- Build Angular ---
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build -- --configuration=production

# --- Nginx estático ---
FROM nginx:1.25-alpine
# 👇 copia el CONTENIDO del directorio "browser" (cualquiera que sea el nombre de la app)
COPY --from=build /app/dist/*/browser/ /usr/share/nginx/html/
COPY nginx.conf /etc/nginx/conf.d/default.conf
