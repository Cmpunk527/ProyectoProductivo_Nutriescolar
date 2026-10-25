<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NutriEscolar – Colegio Técnico Palermo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}

:root{
  --naranja:#f7a600;
  --naranja-oscuro:#d48e00;
  --naranja-claro:#fff3d4;
  --blanco:#ffffff;
  --texto-oscuro:#1a1a1a;
  --gris-suave:#f4f4f4;
  --sombra:rgba(0,0,0,0.18);
}

body{
  font-family:'Nunito','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
  background:var(--gris-suave);
  color:var(--texto-oscuro);
}

/* ====== HEADER ====== */
header{
  position:fixed;
  top:0; left:0; width:100%;
  z-index:100;
  background:var(--naranja);
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 40px;
  height:70px;
  box-shadow:0 2px 12px rgba(0,0,0,0.15);
}

.logo{
  display:flex;
  align-items:center;
  gap:12px;
  text-decoration:none;
}

.logo-icono{
  width:44px;
  height:44px;
  background:white;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:22px;
  font-weight:800;
  color:var(--naranja);
  letter-spacing:-1px;
  flex-shrink:0;
}

.logo-texto{
  display:flex;
  flex-direction:column;
  line-height:1.1;
}

.logo-nombre{
  font-size:20px;
  font-weight:800;
  color:white;
  letter-spacing:0.5px;
}

.logo-subtitulo{
  font-size:10px;
  font-weight:600;
  color:rgba(255,255,255,0.85);
  letter-spacing:0.3px;
  text-transform:uppercase;
}

nav{
  display:flex;
  align-items:center;
  gap:6px;
}

nav a{
  text-decoration:none;
  color:white;
  font-size:14px;
  font-weight:600;
  padding:8px 16px;
  border-radius:20px;
  transition:background 0.2s, color 0.2s;
}

nav a:hover{
  background:rgba(255,255,255,0.2);
}

.btn-ingresar{
  background:white !important;
  color:var(--naranja) !important;
  font-weight:700 !important;
  border-radius:20px;
  padding:8px 20px !important;
  margin-left:10px;
  transition:background 0.2s, transform 0.15s !important;
}

.btn-ingresar:hover{
  background:var(--naranja-claro) !important;
  transform:scale(1.04);
}

/* ====== SLIDER PRINCIPAL ====== */
.slider-box{
  margin-top:70px;
  width:100%;
  height:calc(100vh - 70px);
  overflow:hidden;
  position:relative;
}

.slider-box ul{
  display:flex;
  margin:0;
  padding:0;
  width:400%;
  list-style:none;
  animation:slide 24s infinite ease-in-out;
}

/* Cada diapositiva ocupa exactamente 1/4 del <ul> (= 100% de la pantalla) */
.slider-box li{
  width:25%;
  flex:0 0 25%;
  position:relative;
}

.slider-box img{
  width:100%;
  height:calc(100vh - 70px);
  object-fit:cover;
  display:block;
  filter:brightness(0.62);
}

.overlay-degradado{
  position:absolute;
  inset:0;
  background:linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.55) 100%);
  pointer-events:none;
}

.texto-slide{
  position:absolute;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%);
  text-align:center;
  color:white;
  width:90%;
  max-width:980px;
  text-shadow:1px 2px 12px rgba(0,0,0,0.85);
  padding:0 20px;
}

.slide-badge{
  display:inline-block;
  background:var(--naranja);
  color:white;
  font-size:15px;
  font-weight:700;
  letter-spacing:1.5px;
  text-transform:uppercase;
  padding:8px 22px;
  border-radius:20px;
  margin-bottom:22px;
}

.texto-slide h2{
  font-size:58px;
  font-weight:800;
  margin-bottom:18px;
  line-height:1.15;
}

.texto-slide p{
  font-size:24px;
  font-weight:600;
  line-height:1.6;
  opacity:1;
}

/* Indicadores del slider */
.slider-dots{
  position:absolute;
  bottom:28px;
  left:50%;
  transform:translateX(-50%);
  display:flex;
  gap:10px;
  z-index:10;
}

.dot{
  width:10px;
  height:10px;
  background:rgba(255,255,255,0.5);
  border-radius:50%;
  transition:background 0.3s;
}

.dot.activo{
  background:var(--naranja);
  width:28px;
  border-radius:5px;
}

/* Flecha scroll down */
.scroll-down{
  position:absolute;
  bottom:55px;
  right:40px;
  color:rgba(255,255,255,0.7);
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:4px;
  font-size:12px;
  letter-spacing:1px;
  text-transform:uppercase;
  animation:bouncearrow 2s infinite;
  cursor:pointer;
  text-decoration:none;
}

.scroll-down svg{
  width:22px; height:22px;
  stroke:currentColor; fill:none; stroke-width:2;
}

@keyframes bouncearrow{
  0%,100%{transform:translateY(0);}
  50%{transform:translateY(8px);}
}

@keyframes slide{
  0%{margin-left:0;}
  22%{margin-left:0;}
  27%{margin-left:-100%;}
  47%{margin-left:-100%;}
  52%{margin-left:-200%;}
  72%{margin-left:-200%;}
  77%{margin-left:-300%;}
  97%{margin-left:-300%;}
  100%{margin-left:0;}
}

/* ====== SECCIÓN DESCRIPCIÓN ====== */
.descripcion{
  background:white;
  padding:80px 40px;
  text-align:center;
}

.tag-seccion{
  display:inline-block;
  background:var(--naranja-claro);
  color:var(--naranja-oscuro);
  font-size:15px;
  font-weight:700;
  letter-spacing:1.5px;
  text-transform:uppercase;
  padding:8px 22px;
  border-radius:20px;
  margin-bottom:20px;
}

.descripcion h2{
  font-size:34px;
  font-weight:800;
  color:var(--texto-oscuro);
  margin-bottom:16px;
  line-height:1.2;
}

.descripcion > p{
  font-size:17px;
  color:#555;
  max-width:680px;
  margin:0 auto 56px;
  line-height:1.8;
}

.cards-descripcion{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:24px;
  max-width:1100px;
  margin:0 auto;
}

.card-desc{
  background:var(--gris-suave);
  border-radius:16px;
  padding:32px 24px;
  text-align:left;
  border:1.5px solid transparent;
  transition:border 0.2s, box-shadow 0.2s, transform 0.2s;
  position:relative;
  overflow:hidden;
}

.card-desc::before{
  content:'';
  position:absolute;
  top:0; left:0;
  width:4px; height:100%;
  background:var(--naranja);
  border-radius:16px 0 0 16px;
}

.card-desc:hover{
  border-color:var(--naranja);
  box-shadow:0 8px 30px rgba(247,166,0,0.15);
  transform:translateY(-4px);
}

.card-desc .icono-card{
  width:48px; height:48px;
  background:var(--naranja-claro);
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  margin-bottom:18px;
}

.card-desc .icono-card svg{
  width:26px; height:26px;
  stroke:var(--naranja-oscuro); fill:none; stroke-width:2;
}

.card-desc h3{
  font-size:17px;
  font-weight:700;
  margin-bottom:10px;
  color:var(--texto-oscuro);
}

.card-desc p{
  font-size:14px;
  color:#666;
  line-height:1.7;
}

/* ====== SECCIÓN ACCESO RÁPIDO ====== */
.acceso-rapido{
  background:var(--naranja);
  padding:70px 40px;
  text-align:center;
}

.acceso-rapido h2{
  font-size:30px;
  font-weight:800;
  color:white;
  margin-bottom:10px;
}

.acceso-rapido > p{
  font-size:16px;
  color:rgba(255,255,255,0.88);
  margin-bottom:44px;
}

.btns-acceso{
  display:flex;
  flex-wrap:wrap;
  gap:18px;
  justify-content:center;
  max-width:900px;
  margin:0 auto;
}

.btn-acceso{
  display:flex;
  align-items:center;
  gap:12px;
  text-decoration:none;
  background:white;
  color:var(--naranja-oscuro);
  font-size:15px;
  font-weight:700;
  padding:16px 28px;
  border-radius:12px;
  transition:transform 0.18s, box-shadow 0.18s;
  box-shadow:0 4px 16px rgba(0,0,0,0.12);
  min-width:200px;
}

.btn-acceso svg{
  width:22px; height:22px;
  stroke:var(--naranja); fill:none; stroke-width:2.2;
  flex-shrink:0;
}

.btn-acceso:hover{
  transform:translateY(-3px);
  box-shadow:0 8px 24px rgba(0,0,0,0.18);
}

.btn-acceso-secundario{
  background:rgba(255,255,255,0.15) !important;
  color:white !important;
  border:1.5px solid rgba(255,255,255,0.5);
}

.btn-acceso-secundario svg{
  stroke:white !important;
}

.btn-acceso-secundario:hover{
  background:rgba(255,255,255,0.25) !important;
}

/* ====== SECCIÓN INFORMACIÓN ====== */
.info-proyecto{
  background:white;
  padding:80px 40px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:60px;
  max-width:1200px;
  margin:0 auto;
  align-items:center;
}

.info-imagen{
  border-radius:20px;
  overflow:hidden;
  box-shadow:0 10px 40px rgba(0,0,0,0.12);
}

.info-imagen img{
  width:100%;
  height:400px;
  object-fit:cover;
  display:block;
}

.info-contenido .tag-seccion{margin-bottom:16px;}

.info-contenido h2{
  font-size:32px;
  font-weight:800;
  color:var(--texto-oscuro);
  margin-bottom:20px;
  line-height:1.25;
}

.info-contenido p{
  font-size:16px;
  color:#555;
  line-height:1.8;
  margin-bottom:14px;
}

.lista-info{
  list-style:none;
  margin-top:20px;
  display:flex;
  flex-direction:column;
  gap:12px;
}

.lista-info li{
  display:flex;
  align-items:flex-start;
  gap:12px;
  font-size:15px;
  color:#444;
  line-height:1.5;
}

.check-icono{
  width:22px; height:22px;
  background:var(--naranja-claro);
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
  margin-top:1px;
}

.check-icono svg{
  width:13px; height:13px;
  stroke:var(--naranja-oscuro); fill:none; stroke-width:3;
}

/* ====== FOOTER ====== */
footer{
  background:#1a1a1a;
  color:rgba(255,255,255,0.7);
  padding:40px 40px 24px;
  text-align:center;
}

.footer-logo{
  font-size:22px;
  font-weight:800;
  color:var(--naranja);
  margin-bottom:8px;
}

footer p{
  font-size:13px;
  margin-bottom:6px;
}

.footer-sep{
  height:1px;
  background:rgba(255,255,255,0.1);
  margin:20px 0 16px;
}

footer small{
  font-size:12px;
  color:rgba(255,255,255,0.4);
}

/* ====== RESPONSIVE ====== */
@media(max-width:900px){
  .info-proyecto{
    grid-template-columns:1fr;
    gap:32px;
    padding:60px 24px;
  }
  .texto-slide h2{font-size:36px;}
  .texto-slide p{font-size:19px;}
  nav a{font-size:12px; padding:6px 10px;}
  .logo-subtitulo{display:none;}
}

@media(max-width:600px){
  header{padding:0 16px;}
  .descripcion, .acceso-rapido{padding:50px 20px;}
  .btns-acceso{flex-direction:column; align-items:stretch;}
  .btn-acceso{justify-content:center;}
  nav{gap:2px;}
  nav a:not(.btn-ingresar){display:none;}
}
</style>
</head>
<body>

<!-- ====== HEADER ====== -->
<header>
  <a href="index.php" class="logo">
    <div class="logo-icono">NE</div>
    <div class="logo-texto">
      <span class="logo-nombre">NutriEscolar</span>
      <span class="logo-subtitulo">Colegio Técnico Palermo</span>
    </div>
  </a>
  <nav>
    <a href="ver-menu.php">Ver menú</a>
    <a href="consultar-menu.php">Consultar menú</a>
    <a href="consultar-consumo.php">Mi consumo</a>
    <a href="informacion-nutricional.php">Nutrición</a>
    <?php if (estaLogueado()): ?>
      <a href="<?= esAdmin() ? 'admin-panel.php' : 'ver-menu.php' ?>" class="btn-ingresar">Hola, <?= htmlspecialchars(nombreUsuarioActual()) ?></a>
      <a href="cerrar-sesion.php" class="btn-ingresar">Cerrar sesión</a>
    <?php else: ?>
      <a href="inicio-sesion.php" class="btn-ingresar">Ingresar</a>
    <?php endif; ?>
  </nav>
</header>

<!-- ====== SLIDER PRINCIPAL ====== -->
<section class="slider-box">
  <ul>
    <!-- SLIDE 1 -->
    <li>
      <img src="Imagenes/Alimentacion_escolar_Bogota_1.jpg"
           alt="Alimentación escolar Bogotá"
           onerror="this.src='https://bogota.gov.co/sites/default/files/inline-images/programa-de-alimentacion-escolar-pae.png'">
      <div class="overlay-degradado"></div>
      <div class="texto-slide">
        <span class="slide-badge">Bienvenidos</span>
        <h2>Gestión Nutricional Escolar Digital</h2>
        <p>Consulta, controla y administra toda la información nutricional del servicio de alimentación del Colegio Técnico Palermo desde un solo lugar.</p>
      </div>
    </li>
    <!-- SLIDE 2 -->
    <li>
      <img src="Imagenes/imagen 2.png"
           alt="Alimentación saludable"
           onerror="this.src='https://bogota.gov.co/sites/default/files/inline-images/programa-de-alimentacion-escolar-pae.png'">
      <div class="overlay-degradado"></div>
      <div class="texto-slide">
        <span class="slide-badge">Objetivo</span>
        <h2>Digitalizar el Registro Alimentario</h2>
        <p>Implementamos un sistema funcional que elimina los procesos manuales y mejora el acceso a la información para toda la comunidad educativa.</p>
      </div>
    </li>
    <!-- SLIDE 3 -->
    <li>
      <img src="Imagenes/Alimentacion_escolar-Foto_1.jpg"
           alt="Estudiantes comiendo"
           onerror="this.src='https://bogota.gov.co/sites/default/files/inline-images/programa-de-alimentacion-escolar-pae.png'">
      <div class="overlay-degradado"></div>
      <div class="texto-slide">
        <span class="slide-badge">¿A quién va dirigido?</span>
        <h2>Para Toda la Comunidad del Palermo</h2>
        <p>Estudiantes, docentes, acudientes y personal administrativo pueden consultar y administrar de forma rápida y segura la información nutricional escolar.</p>
      </div>
    </li>
    <!-- SLIDE 4 -->
    <li>
      <img src="Imagenes/imagen 4.jpg"
           alt="Alimentación escolar"
           onerror="this.src='https://bogota.gov.co/sites/default/files/inline-images/programa-de-alimentacion-escolar-pae.png'">
      <div class="overlay-degradado"></div>
      <div class="texto-slide">
        <span class="slide-badge">¿Por qué?</span>
        <h2>Modernizar la Gestión Alimentaria</h2>
        <p>Reducimos errores, promovemos hábitos saludables y facilitamos la toma de decisiones dentro de la institución con datos claros y organizados.</p>
      </div>
    </li>
  </ul>

  <!-- Indicadores -->
  <div class="slider-dots" id="dots">
    <div class="dot activo"></div>
    <div class="dot"></div>
    <div class="dot"></div>
    <div class="dot"></div>
  </div>

  <!-- Scroll down -->
  <a href="#descripcion" class="scroll-down">
    <span>Explorar</span>
    <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
  </a>
</section>

<!-- ====== SECCIÓN DESCRIPCIÓN ====== -->
<section class="descripcion" id="descripcion">
  <span class="tag-seccion">¿Qué ofrecemos?</span>
  <h2>Todo lo que necesitas sobre<br>la alimentación escolar</h2>
  <p>NutriEscolar centraliza la gestión del menú, el control del consumo y la información nutricional de los alimentos del colegio en una sola plataforma.</p>

  <div class="cards-descripcion">
    <div class="card-desc">
      <div class="icono-card">
        <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="12" y2="16"/></svg>
      </div>
      <h3>Consultar Menú</h3>
      <p>Revisa el menú diario y semanal programado por el equipo de nutrición, filtrado por día o fecha.</p>
    </div>
    <div class="card-desc">
      <div class="icono-card">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <h3>Información Nutricional</h3>
      <p>Accede a los valores nutricionales detallados de cada alimento: calorías, proteínas, carbohidratos y más.</p>
    </div>
    <div class="card-desc">
      <div class="icono-card">
        <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3z" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
      </div>
      <h3>Consultar Consumo</h3>
      <p>Los estudiantes y acudientes pueden revisar el historial de consumo alimentario y el seguimiento nutricional.</p>
    </div>
    <div class="card-desc">
      <div class="icono-card">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
      </div>
      <h3>Panel Administrativo</h3>
      <p>Los administradores gestionan alimentos, menús y registros nutricionales con herramientas completas.</p>
    </div>
  </div>
</section>

<!-- ====== ACCESO RÁPIDO ====== -->
<section class="acceso-rapido">
  <h2>Acceso rápido</h2>
  <p>Dirígete directamente a la sección que necesitas</p>
  <div class="btns-acceso">
    <a href="ver-menu.php" class="btn-acceso">
      <svg viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
      Ver menú de hoy
    </a>
    <a href="consultar-menu.php" class="btn-acceso">
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Consultar menú por fecha
    </a>
    <a href="informacion-nutricional.php" class="btn-acceso">
      <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      Información nutricional
    </a>
    <a href="consultar-consumo.php" class="btn-acceso">
      <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Mi consumo
    </a>
    <a href="<?= estaLogueado() ? (esAdmin() ? 'admin-panel.php' : 'ver-menu.php') : 'inicio-sesion.php' ?>" class="btn-acceso btn-acceso-secundario">
      <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      <?= estaLogueado() ? (esAdmin() ? 'Ir al panel admin' : 'Iniciar sesión') : 'Iniciar sesión' ?>
    </a>
  </div>
</section>

<!-- ====== INFO DEL PROYECTO ====== -->
<section style="background:white; padding:80px 40px;">
  <div class="info-proyecto" style="padding:0;">
    <div class="info-imagen">
      <img src="Imagenes/imagen 2.png"
           alt="Alimentación escolar saludable"
           onerror="this.src='https://bogota.gov.co/sites/default/files/inline-images/programa-de-alimentacion-escolar-pae.png'">
    </div>
    <div class="info-contenido">
      <span class="tag-seccion">Sobre el proyecto</span>
      <h2>¿Por qué NutriEscolar?</h2>
      <p>El proyecto surge de la necesidad de modernizar y optimizar la gestión de la información nutricional en el Colegio Técnico Palermo, donde los procesos manuales generaban dificultades en el acceso a los datos y aumentaban el riesgo de errores.</p>
      <ul class="lista-info">
        <li>
          <div class="check-icono"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          Digitalización completa del registro de alimentos y menús semanales
        </li>
        <li>
          <div class="check-icono"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          Reducción de errores en los procesos de gestión alimentaria
        </li>
        <li>
          <div class="check-icono"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          Acceso rápido y seguro para toda la comunidad educativa
        </li>
        <li>
          <div class="check-icono"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          Promoción de hábitos alimenticios saludables en los estudiantes
        </li>
        <li>
          <div class="check-icono"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
          Herramientas administrativas para nutricionistas y coordinadores
        </li>
      </ul>
    </div>
  </div>
</section>

<!-- ====== FOOTER ====== -->
<footer>
  <div class="footer-logo">NutriEscolar</div>
  <p>Colegio Técnico Palermo — Sistema de Gestión Nutricional</p>
  <p>Desarrollo de Aplicación Web y Móvil</p>
  <div class="footer-sep"></div>
  <small>© 2026 NutriEscolar – Proyecto académico Colegio Técnico Palermo. Todos los derechos reservados.</small>
</footer>

<!-- ====== SCRIPT DOTS ====== -->
<script>
const totalSlides = 4;
const intervalMs = 6000;
let current = 0;

function actualizarDots(idx){
  document.querySelectorAll('.dot').forEach((d,i)=>{
    d.classList.toggle('activo', i === idx);
  });
}

setInterval(()=>{
  current = (current + 1) % totalSlides;
  actualizarDots(current);
}, intervalMs);

document.querySelector('.scroll-down').addEventListener('click', e=>{
  e.preventDefault();
  document.getElementById('descripcion').scrollIntoView({behavior:'smooth'});
});
</script>
</body>
</html>
