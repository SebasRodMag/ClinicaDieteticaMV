# Etapa de build
FROM node:20.13.1-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
# Ajusta el nombre de tu app en dist si es distinto
RUN npm run build -- --configuration production

# Etapa de runtime (Nginx)
FROM nginx:alpine
COPY docker/nginx-frontend.conf /etc/nginx/conf.d/default.conf
# ⚠️ Ajusta el nombre de carpeta dist si no es "clinica-dietetica"
COPY --from=build /app/dist/clinica-dietetica/ /usr/share/nginx/html
EXPOSE 80