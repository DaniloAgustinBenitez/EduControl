// Caso de prueba CP-TR-01: inscripción al transporte con un único recorrido (RF-11, RN-06, HU7).
import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { BASE, By, abrirNavegador, aviso, clic, enviar, ingresar, tarjeta } from './apoyo.js';

const SOFIA = `${BASE}/autogestion/alumnos/1`;
let driver;
let norte;
let sur;

// Valor del radio de un recorrido a partir del texto de su etiqueta.
async function idDeRecorrido(nombre) {
    const etiqueta = await driver.findElement(By.xpath(`//label[contains(normalize-space(), '${nombre}')]`));
    return (await etiqueta.getAttribute('for')).replace('rec', '');
}

async function darDeBajaElTransporte() {
    await enviar(driver, await tarjeta(driver, 'Transporte').findElement(By.xpath(".//button[normalize-space()='Dar de baja']")));
}

before(async () => {
    driver = await abrirNavegador();
    // Condición previa: el tutor ingresa y Sofía queda sin transporte.
    await ingresar(driver, 'tutor@educontrol.test');
    await driver.get(SOFIA);
    await driver.executeScript('window.confirm = () => true');
    if ((await tarjeta(driver, 'Transporte').findElements(By.name('recorrido_id'))).length === 0) {
        await darDeBajaElTransporte();
    }
});

after(async () => driver?.quit());

test('CP-TR-01 paso 1: el transporte figura sin inscribir y ofrece los recorridos', async () => {
    const transporte = await tarjeta(driver, 'Transporte');
    assert.equal(await transporte.findElement(By.css('.ec-pill')).getText(), 'Sin inscribir');
    assert.ok((await transporte.findElements(By.name('recorrido_id'))).length >= 2);
    norte = await idDeRecorrido('Norte');
    sur = await idDeRecorrido('Sur');
});

test('CP-TR-01 paso 2: inscribe a Sofía al recorrido Norte', async () => {
    await clic(driver, await driver.findElement(By.id(`rec${norte}`)));
    await enviar(driver, await driver.findElement(By.xpath("//button[normalize-space()='Inscribir al recorrido']")));

    const { exito, error } = await aviso(driver);
    assert.ok(exito && !error, `se esperaba un mensaje de éxito: ${exito || error}`);
    const transporte = await tarjeta(driver, 'Transporte');
    assert.equal(await transporte.findElement(By.css('.ec-pill')).getText(), 'Inscripto');
    assert.match(await transporte.getText(), /Recorrido Norte/);
});

test('CP-TR-01 paso 3: la pantalla ya no ofrece otro recorrido', async () => {
    const transporte = await tarjeta(driver, 'Transporte');
    assert.equal((await transporte.findElements(By.name('recorrido_id'))).length, 0);
    assert.match(await transporte.getText(), /Solo se permite un recorrido/);
});

test('CP-TR-01 paso 4: rechaza un segundo recorrido aunque se fuerce el envío (RN-06)', async () => {
    // Se arma a mano el formulario que la pantalla ya no muestra, como lo haría alguien que lo envía desde afuera.
    await driver.executeScript(`
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = arguments[0];
        f.innerHTML = '<input name="_token" value="' + document.querySelector('input[name=_token]').value + '">'
            + '<input name="recorrido_id" value="' + arguments[1] + '">';
        document.body.appendChild(f);
        f.submit();`, `${SOFIA}/transporte`, sur);
    await driver.wait(async () => (await driver.findElements(By.css('.alert'))).length > 0, 10000);

    const { exito, error } = await aviso(driver);
    assert.ok(error && !exito, 'se esperaba un mensaje de error');
    assert.match(error, /Solo se permite un recorrido/);
    assert.match(await (await tarjeta(driver, 'Transporte')).getText(), /Recorrido Norte/);
});

test('CP-TR-01 paso 5: después de la baja puede elegir otro recorrido', async () => {
    await driver.executeScript('window.confirm = () => true');
    await darDeBajaElTransporte();
    assert.equal(await (await tarjeta(driver, 'Transporte')).findElement(By.css('.ec-pill')).getText(), 'Sin inscribir');

    await clic(driver, await driver.findElement(By.id(`rec${sur}`)));
    await enviar(driver, await driver.findElement(By.xpath("//button[normalize-space()='Inscribir al recorrido']")));
    assert.match(await (await tarjeta(driver, 'Transporte')).getText(), /Recorrido Sur/);

    // Deja a Sofía como estaba al empezar.
    await driver.executeScript('window.confirm = () => true');
    await darDeBajaElTransporte();
});
