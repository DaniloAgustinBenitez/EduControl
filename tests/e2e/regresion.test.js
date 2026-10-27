// Pruebas de regresión: defectos ya corregidos que no deben volver a aparecer.
import assert from 'node:assert/strict';
import { after, test } from 'node:test';
import { BASE, By, CELULAR, abrirNavegador, ingresar, textoDeLaPagina } from './apoyo.js';

// El ingreso tiene un límite de intentos por minuto: cada usuario ingresa una sola vez.
const abiertos = [];
let tutor;
after(async () => Promise.all(abiertos.map(d => d.quit())));

async function navegador(tamano) {
    const driver = await abrirNavegador(tamano);
    abiertos.push(driver);
    return driver;
}

// Campos visibles con letra de menos de 16 px (en iPhone, tocarlos agranda la pantalla).
async function camposChicos(driver) {
    return driver.executeScript(`
        return [...document.querySelectorAll('input:not([type=hidden]):not([type=radio]):not([type=checkbox]), select, textarea')]
            .filter(e => e.offsetParent !== null && parseFloat(getComputedStyle(e).fontSize) < 16)
            .map(e => (e.name || e.getAttribute('aria-label') || e.tagName) + ' ' + getComputedStyle(e).fontSize);`);
}

test('NC-09: en el celular, los campos de la autogestión miden 16 px o más', async () => {
    tutor = await navegador(CELULAR);
    assert.ok(await tutor.executeScript('return window.innerWidth') < 576, 'la ventana tiene que ser de celular');
    await ingresar(tutor, 'tutor@educontrol.test');
    await tutor.get(`${BASE}/autogestion/alumnos/2`);
    assert.ok((await tutor.findElements(By.name('deporte_id'))).length > 0, 'la pantalla tiene que mostrar el selector de deportes');
    assert.deepEqual(await camposChicos(tutor), []);
});

test('NC-09: en el celular, el buscador de las tablas mide 16 px o más', async () => {
    const driver = await navegador(CELULAR);
    await ingresar(driver, 'admin@educontrol.test');
    await driver.get(`${BASE}/alumnos`);
    await driver.wait(async () => (await driver.findElements(By.css('.dt-search input'))).length > 0, 10000);
    assert.deepEqual(await camposChicos(driver), []);
});

test('RN-07: el tutor no puede abrir la ficha de un alumno que no es su hijo', async () => {
    await tutor.get(`${BASE}/autogestion/alumnos/3`);
    const texto = await textoDeLaPagina(tutor);
    assert.match(texto, /403/);
    assert.doesNotMatch(texto, /Lucía/);
});
