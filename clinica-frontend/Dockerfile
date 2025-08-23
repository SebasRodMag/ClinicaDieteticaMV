# --- Build Angular ---
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
# si tu app se llama "clinica-frontend" (lo es en package.json)
RUN npm run build -- --configuration=production

# --- Nginx estático ---
FROM nginx:1.25-alpine
# 👇 Copiar el CONTENIDO de browser a /usr/share/nginx/html
COPY --from=build /app/dist/clinica-frontend/browser/ /usr/share/nginx/html/
# tu config de nginx para SPA
COPY nginx.conf /etc/nginx/conf.d/default.conf
