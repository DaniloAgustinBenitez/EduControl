// Funciones comunes de las pruebas de navegador con Selenium WebDriver.
// La aplicación tiene que estar corriendo con los datos de prueba (php artisan migrate:fresh --seed).
import webdriver from 'selenium-webdriver';
import chrome from 'selenium-webdriver/chrome.js';
import edge from 'selenium-webdriver/edge.js';

const { Builder, By, until } = webdriver;
export { By, until };

export const BASE = (process.env.E2E_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const NAVEGADOR = process.env.E2E_NAVEGADOR ?? 'MicrosoftEdge'; // en el CI: chrome
const ESPERA = 10000;

export const ESCRITORIO = { ancho: 1366, alto: 850 };
export const CELULAR = { ancho: 390, alto: 844 };

// Sin gestor de contraseñas: después de ingresar con la clave de prueba ("password"), Chrome abre el aviso
// de contraseña filtrada por encima de la página y los clics no le llegan a la aplicación.
const PREFERENCIAS = {
    credentials_enable_service: false,
    'profile.password_manager_enabled': false,
    'profile.password_manager_leak_detection': false,
};

export async function abrirNavegador({ ancho, alto } = ESCRITORIO) {
    const argumentos = ['--headless=new', `--window-size=${ancho},${alto}`, '--lang=es-AR', '--disable-features=PasswordLeakDetection'];
    const builder = new Builder().forBrowser(NAVEGADOR);
    if (NAVEGADOR === 'chrome') {
        builder.setChromeOptions(new chrome.Options().addArguments(...argumentos, '--no-sandbox').setUserPreferences(PREFERENCIAS));
    } else {
        builder.setEdgeOptions(new edge.Options().addArguments(...argumentos).setUserPreferences(PREFERENCIAS));
    }
    return builder.build();
}

export async function ingresar(driver, email) {
    await driver.get(`${BASE}/login`);
    await driver.findElement(By.id('email')).sendKeys(email);
    await driver.findElement(By.id('password')).sendKeys('password');
    await enviar(driver, await driver.findElement(By.xpath("//button[normalize-space()='Ingresar']")));

    // El ingreso admite 6 intentos por minuto (throttle:6,1): correr la suite varias veces seguidas lo agota.
    const url = await driver.getCurrentUrl();
    const texto = await textoDeLaPagina(driver);
    if (url.endsWith('/login') || /429|Too Many/i.test(texto)) {
        throw new Error(`No se pudo ingresar como ${email}: ${texto.slice(0, 120)}. Si es el límite de intentos, esperá un minuto.`);
    }
}

// Lleva el elemento al centro de la pantalla antes de hacer clic, como lo haría una persona.
// Sin animación: Bootstrap usa desplazamiento suave y el clic caería a mitad de camino.
export async function clic(driver, elemento) {
    await driver.executeScript("arguments[0].scrollIntoView({ block: 'center', behavior: 'instant' })", elemento);
    await elemento.click();
}

// Marca un radio o checkbox y comprueba que quedó marcado.
export async function elegir(driver, opcion) {
    await clic(driver, opcion);
    if (!(await opcion.isSelected())) {
        throw new Error('No se pudo marcar la opción: el clic no llegó a la página');
    }
}

// Hace clic y espera a que cargue la página siguiente.
export async function enviar(driver, elemento) {
    await clic(driver, elemento);
    await driver.wait(until.stalenessOf(elemento), ESPERA);
}

// Mensajes de éxito y de error que muestra la aplicación después de cada acción.
export async function aviso(driver) {
    const textos = async selector => Promise.all((await driver.findElements(By.css(selector))).map(e => e.getText()));
    return { exito: (await textos('.alert-success')).join(' '), error: (await textos('.alert-danger')).join(' ') };
}

export async function textoDeLaPagina(driver) {
    return driver.findElement(By.css('body')).getText();
}

export function tarjeta(driver, titulo) {
    return driver.findElement(By.xpath(`//div[contains(@class,'card')][.//h2[normalize-space()='${titulo}']]`));
}
