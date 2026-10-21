# ==============================================================
# Frontend Dockerfile – Vue.js Multi-Stage Build
# Stage 1: Build dengan Node.js (versi spesifik, bukan latest)
# ==============================================================
FROM node:22.12.0-alpine AS builder

WORKDIR /app

# Cache layer: salin package.json dan package-lock.json terlebih dahulu
COPY package*.json ./
RUN npm ci

# Salin source code
COPY . .

# Argument VITE_API_BASE jika ingin disesuaikan saat build
ARG VITE_API_BASE=/api
ENV VITE_API_BASE=${VITE_API_BASE}

# Jalankan build Vue (menghasilkan folder dist/)
RUN npm run build

# ==============================================================
# Stage 2: Production Nginx Server (versi spesifik, bukan latest)
# Image akhir bersih tanpa node_modules
# ==============================================================
FROM nginx:1.25.5-alpine AS runner

# Hapus file default Nginx
RUN rm -rf /usr/share/nginx/html/*

# Salin konfigurasi Nginx reverse proxy
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Salin artefak hasil build dari stage 1 ke web root Nginx
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
