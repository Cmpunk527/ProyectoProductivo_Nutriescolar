<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ver Menú – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
body{background:#111;}
.slider-wrap{margin-top:70px;width:100%;overflow:hidden;position:relative;}
.slider-track{display:flex;width:400%;animation:slide 24s infinite ease-in-out;}
.slide-item{width:25%;min-width:0;flex:0 0 25%;position:relative;}
.slide-item img{
  width:100%;height:calc(100vh - 70px);
  object-fit:cover;display:block;filter:brightness(0.5);
}
.slide-texto{
  position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  text-align:center;color:white;
  width:90%;max-width:1000px;
  text-shadow:1px 2px 12px rgba(0,0,0,0.9);
}
.slide-badge{
  display:inline-block;
  background:var(--naranja);color:white;
  font-size:15px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
  padding:8px 22px;border-radius:20px;margin-bottom:22px;
}
.slide-texto h2{font-size:58px;font-weight:800;margin-bottom:18px;line-height:1.15;}
.slide-texto p{font-size:25px;font-weight:600;line-height:1.6;opacity:1;}
.dots{
  position:absolute;bottom:24px;left:50%;transform:translateX(-50%);
  display:flex;gap:10px;z-index:10;
}
.dot{width:10px;height:10px;background:rgba(255,255,255,0.4);border-radius:50%;transition:all 0.3s;}
.dot.activo{background:var(--naranja);width:26px;border-radius:5px;}
@keyframes slide{
  0%{margin-left:0;}22%{margin-left:0;}
  27%{margin-left:-100%;}47%{margin-left:-100%;}
  52%{margin-left:-200%;}72%{margin-left:-200%;}
  77%{margin-left:-300%;}97%{margin-left:-300%;}100%{margin-left:0;}
}
@media(max-width:1000px){
  .slide-texto h2{font-size:42px;}
  .slide-texto p{font-size:21px;}
}
@media(max-width:640px){
  .slide-item img{height:calc(100vh - 70px);}
  .slide-texto h2{font-size:30px;margin-bottom:10px;}
  .slide-texto p{font-size:18px;}
  .slide-badge{font-size:13px;}
}
@media(max-width:380px){
  .slide-texto h2{font-size:25px;}
  .slide-texto p{font-size:16px;}
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icono">NE</div>
    <span class="nav-logo-nombre">NutriEscolar</span>
  </a>
  <button class="hamburger" id="hamburger" aria-label="Menú">
    <span></span><span></span><span></span>
  </button>
  <div class="nav-menu" id="navMenu">
    <a href="registrar-consumo.php">Registrar consumo</a>
    <a href="consultar-consumo.php">Mi consumo</a>
    <a href="consultar-menu.php">Consultar menú</a>
    <a href="informacion-nutricional.php">Nutrición</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<div class="slider-wrap">
  <div class="slider-track">
    <div class="slide-item">
      <img src="Imagenes/Alimentacion_escolar_Bogota_1.jpg" alt="Alimentación escolar">
      <div class="slide-texto">
        <span class="slide-badge">Bienvenido, <?= htmlspecialchars(nombreUsuarioActual()) ?></span>
        <h2>Objetivo del Proyecto</h2>
        <p>Gestionar, controlar y consultar la información nutricional de los alimentos del Colegio Técnico Palermo, facilitando el acceso a datos claros y organizados.</p>
      </div>
    </div>
    <div class="slide-item">
      <img src="Imagenes/imagen 2.png" alt="Meta">
      <div class="slide-texto">
        <span class="slide-badge">Meta</span>
        <h2>Digitalizar el Registro Alimentario</h2>
        <p>Implementar un sistema funcional que digitalice el registro de alimentos, menús y valores nutricionales, reduciendo errores manuales.</p>
      </div>
    </div>
    <div class="slide-item">
      <img src="Imagenes/Alimentacion_escolar-Foto_1.jpg" alt="Dirigido a">
      <div class="slide-texto">
        <span class="slide-badge">¿A quién va dirigido?</span>
        <h2>Para Toda la Comunidad del Palermo</h2>
        <p>Estudiantes, docentes, acudientes y personal administrativo pueden consultar la información nutricional de los alimentos escolares.</p>
      </div>
    </div>
    <div class="slide-item">
      <img src="Imagenes/imagen 4.jpg" alt="Por qué">
      <div class="slide-texto">
        <span class="slide-badge">¿Por qué?</span>
        <h2>Modernizar la Gestión Alimentaria</h2>
        <p>Reducimos errores, promovemos hábitos saludables y facilitamos la toma de decisiones dentro de la institución con datos confiables.</p>
      </div>
    </div>
  </div>
  <div class="dots" id="dots">
    <div class="dot activo"></div>
    <div class="dot"></div>
    <div class="dot"></div>
    <div class="dot"></div>
  </div>
</div>

<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
let cur=0;
setInterval(()=>{cur=(cur+1)%4;document.querySelectorAll('.dot').forEach((d,i)=>d.classList.toggle('activo',i===cur));},6000);
</script>
</body>
</html>
