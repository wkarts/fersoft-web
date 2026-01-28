$(function(){
	
	setInterval(() => {
		verificaArquivoCatraca()
	}, TIMER*1000)

})
function verificaArquivoCatraca(){

	$.get(path+'pedidos/get-comandas-fechadas')
	.done((xml) => {
		if(xml != ''){
			criarXml(xml)
		}else{

		}
	}).fail((err) => {
		console.log(err)
	})
	
	$.get(path+'pedidos/get-comandas-novas')
	.done((xml) => {
		if(xml != ''){
			criarXml(xml)
		}else{

		}
	}).fail((err) => {
		console.log(err)
	})
	
}

function criarXml(xmltext){
	console.log("baixando..")
	const date = new Date()
	var filename = makeRandom(25)+".xml";

	var pom = document.createElement('a');
	var bb = new Blob([xmltext], {type: 'text/plain'});

	pom.setAttribute('href', window.URL.createObjectURL(bb));
	pom.setAttribute('download', filename);

	pom.dataset.downloadurl = ['text/plain', pom.download, pom.href].join(':');
	pom.draggable = true; 
	pom.classList.add('dragout');

	pom.click();
}

function makeRandom(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
      counter += 1;
    }
    return result;
}