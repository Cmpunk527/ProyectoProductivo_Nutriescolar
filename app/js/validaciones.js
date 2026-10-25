/* ============================================================
   NutriEscolar – validaciones.js
   Módulo central de restricciones y validaciones de formularios.
   Se integra en TODAS las páginas que contienen campos de
   texto (letras), campos numéricos, correos y contraseñas.

   Cómo se activa cada restricción (mediante atributos data-*):
   - data-validar="letras"   -> solo letras, tildes, ñ y espacios
   - data-validar="numeros"  -> solo números (permite un solo punto decimal)
   - type="email"            -> se valida formato de correo automáticamente
   - data-pass="nueva"       -> contraseña principal (valida longitud mínima)
   - data-pass="confirmar"   -> debe coincidir con data-pass="nueva"
   - form[data-validar-form] -> activa la validación completa al enviar
   ============================================================ */

(function () {
  "use strict";

  // Expresiones regulares reutilizables
  const REGEX_LETRAS = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/;
  const REGEX_LETRA_UNICA = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]$/;
  const REGEX_NUMERO_UNICO = /^[0-9]$/;
  const REGEX_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const LONGITUD_MIN_PASSWORD = 6;

  /* ---------- Utilidades de UI (mensajes / estilos de error) ---------- */

  function obtenerContenedorMensaje(campo) {
    // Busca (o crea) un <small> justo después del campo para mostrar el error
    let contenedor = campo.parentElement;
    let mensaje = contenedor.querySelector(":scope > .mensaje-error");
    if (!mensaje) {
      mensaje = document.createElement("small");
      mensaje.className = "mensaje-error";
      // Si el campo está dentro de .campo (input flotante), el mensaje va después del label
      contenedor.appendChild(mensaje);
    }
    return mensaje;
  }

  function marcarError(campo, texto) {
    campo.classList.add("campo-invalido");
    const label = campo.parentElement.querySelector("label");
    if (label) label.classList.add("label-error");
    const mensaje = obtenerContenedorMensaje(campo);
    mensaje.textContent = texto || "";
  }

  function limpiarError(campo) {
    campo.classList.remove("campo-invalido");
    const label = campo.parentElement.querySelector("label");
    if (label) label.classList.remove("label-error");
    const mensaje = campo.parentElement.querySelector(":scope > .mensaje-error");
    if (mensaje) mensaje.textContent = "";
  }

  /* ---------- Restricción: SOLO LETRAS ---------- */

  function permitirSoloLetras(input) {
    // Bloquea la tecla mientras se escribe
    input.addEventListener("keypress", function (e) {
      if (e.ctrlKey || e.metaKey || e.altKey) return; // atajos de teclado
      if (!REGEX_LETRA_UNICA.test(e.key)) {
        e.preventDefault();
      }
    });

    // Red de seguridad: limpia cualquier carácter inválido (autocompletado, etc.)
    input.addEventListener("input", function () {
      const limpio = this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, "");
      if (limpio !== this.value) this.value = limpio;
      if (this.value.trim() !== "") limpiarError(this);
    });

    // Bloquea pegado de texto con números o símbolos
    input.addEventListener("paste", function (e) {
      e.preventDefault();
      const texto = (e.clipboardData || window.clipboardData).getData("text");
      const limpio = texto.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, "");
      insertarTexto(this, limpio);
    });

    input.addEventListener("blur", function () {
      if (this.value.trim() !== "" && !REGEX_LETRAS.test(this.value.trim())) {
        marcarError(this, "Este campo solo admite letras.");
      }
    });
  }

  /* ---------- Restricción: SOLO NÚMEROS ---------- */

  function permitirSoloNumeros(input) {
    input.addEventListener("keypress", function (e) {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      const permiteDecimales = this.dataset.decimales !== "false";
      const esPunto = e.key === "." || e.key === ",";

      if (esPunto && permiteDecimales) {
        // Solo se permite un separador decimal
        if (this.value.includes(".") || this.value.includes(",")) {
          e.preventDefault();
        }
        return;
      }
      if (!REGEX_NUMERO_UNICO.test(e.key)) {
        e.preventDefault();
      }
    });

    input.addEventListener("input", function () {
      const permiteDecimales = this.dataset.decimales !== "false";
      let limpio = permiteDecimales
        ? this.value.replace(/[^0-9.,]/g, "")
        : this.value.replace(/[^0-9]/g, "");

      // Normaliza coma a punto y evita más de un separador decimal
      limpio = limpio.replace(",", ".");
      const partes = limpio.split(".");
      if (partes.length > 2) {
        limpio = partes[0] + "." + partes.slice(1).join("");
      }
      if (limpio !== this.value) this.value = limpio;
      if (this.value.trim() !== "") limpiarError(this);
    });

    input.addEventListener("paste", function (e) {
      e.preventDefault();
      const texto = (e.clipboardData || window.clipboardData).getData("text");
      const limpio = texto.replace(/[^0-9.,]/g, "");
      insertarTexto(this, limpio);
    });

    input.addEventListener("blur", function () {
      if (this.value.trim() !== "" && isNaN(Number(this.value))) {
        marcarError(this, "Este campo solo admite números.");
      }
    });
  }

  // Inserta texto en la posición del cursor (usado al bloquear "pegar")
  function insertarTexto(input, texto) {
    const inicio = input.selectionStart || 0;
    const fin = input.selectionEnd || 0;
    const valorActual = input.value;
    input.value = valorActual.slice(0, inicio) + texto + valorActual.slice(fin);
    const nuevaPosicion = inicio + texto.length;
    input.setSelectionRange(nuevaPosicion, nuevaPosicion);
    input.dispatchEvent(new Event("input"));
  }

  /* ---------- Validación de correo electrónico ---------- */

  function validarCampoEmail(input) {
    if (input.value.trim() !== "" && !REGEX_EMAIL.test(input.value.trim())) {
      marcarError(input, "Ingrese un correo electrónico válido (ejemplo: nombre@correo.com).");
      return false;
    }
    limpiarError(input);
    return true;
  }

  /* ---------- Validación de contraseñas ---------- */

  function validarContrasenas(form) {
    const nueva = form.querySelector('[data-pass="nueva"]');
    const confirmar = form.querySelector('[data-pass="confirmar"]');
    let valido = true;

    if (nueva) {
      if (nueva.value.length < LONGITUD_MIN_PASSWORD) {
        marcarError(nueva, `La contraseña debe tener al menos ${LONGITUD_MIN_PASSWORD} caracteres.`);
        valido = false;
      } else {
        limpiarError(nueva);
      }
    }

    if (confirmar) {
      if (nueva && confirmar.value !== nueva.value) {
        marcarError(confirmar, "Las contraseñas no coinciden.");
        valido = false;
      } else {
        limpiarError(confirmar);
      }
    }

    return valido;
  }

  /* ---------- Validación de un campo individual (uso genérico) ---------- */

  function validarCampo(campo) {
    let valido = true;

    if (campo.hasAttribute("required") && campo.value.trim() === "") {
      marcarError(campo, "Este campo es obligatorio.");
      return false;
    }

    if (campo.dataset.validar === "letras" && campo.value.trim() !== "") {
      if (!REGEX_LETRAS.test(campo.value.trim())) {
        marcarError(campo, "Este campo solo admite letras.");
        valido = false;
      }
    }

    if (campo.dataset.validar === "numeros" && campo.value.trim() !== "") {
      if (isNaN(Number(campo.value))) {
        marcarError(campo, "Este campo solo admite números.");
        valido = false;
      }
    }

    if (campo.type === "email" && campo.value.trim() !== "") {
      valido = validarCampoEmail(campo) && valido;
    }

    if (valido) limpiarError(campo);
    return valido;
  }

  /* ---------- Validación de campos que están fuera de un <form>
     (páginas con div.formulario + botón con onclick), expuesta
     globalmente para poder llamarla antes de ejecutar la acción ---------- */

  window.validarCamposFormulario = function (contenedor) {
    if (!contenedor) return true;
    let formularioValido = true;

    contenedor.querySelectorAll("input, select").forEach(function (campo) {
      const esRequeridoImplicito = campo.tagName === "SELECT" || campo.hasAttribute("required") || campo.dataset.validar;
      if (!esRequeridoImplicito) return;

      if (campo.tagName === "SELECT") {
        if (!campo.value) {
          formularioValido = false;
          campo.classList.add("campo-invalido");
        } else {
          campo.classList.remove("campo-invalido");
        }
        return;
      }

      if (!validarCampo(campo)) {
        formularioValido = false;
      }
    });

    if (!validarContrasenas(contenedor)) {
      formularioValido = false;
    }

    if (!formularioValido) {
      alert("Por favor revise los campos marcados en rojo antes de continuar.");
    }

    return formularioValido;
  };

  /* ---------- Inicialización al cargar el DOM ---------- */

  document.addEventListener("DOMContentLoaded", function () {
    // 1) Restricciones de teclado: letras y números
    document.querySelectorAll('[data-validar="letras"]').forEach(permitirSoloLetras);
    document.querySelectorAll('[data-validar="numeros"]').forEach(permitirSoloNumeros);

    // 2) Validación de correos al salir del campo (blur)
    document.querySelectorAll('input[type="email"]').forEach(function (input) {
      input.addEventListener("blur", function () {
        validarCampoEmail(this);
      });
      input.addEventListener("input", function () {
        if (this.value.trim() === "" || REGEX_EMAIL.test(this.value.trim())) {
          limpiarError(this);
        }
      });
    });

    // 3) Validación de contraseñas en tiempo real
    document.querySelectorAll('[data-pass="confirmar"]').forEach(function (input) {
      input.addEventListener("input", function () {
        const form = this.closest("form") || this.closest(".card");
        if (form) validarContrasenas(form);
      });
    });

    // 4) Validación completa al enviar formularios (<form data-validar-form>)
    document.querySelectorAll("form[data-validar-form]").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        let esValido = true;

        form.querySelectorAll("input, select").forEach(function (campo) {
          if (campo.tagName === "SELECT") {
            if (campo.hasAttribute("required") && !campo.value) {
              esValido = false;
              campo.classList.add("campo-invalido");
            } else {
              campo.classList.remove("campo-invalido");
            }
            return;
          }
          if (!validarCampo(campo)) esValido = false;
        });

        if (!validarContrasenas(form)) esValido = false;

        if (!esValido) {
          e.preventDefault();
          const primerError = form.querySelector(".campo-invalido");
          if (primerError) primerError.focus();
        }
      });
    });
  });
})();
