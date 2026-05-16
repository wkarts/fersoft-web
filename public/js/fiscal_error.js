(function(window){
	'use strict';

	function stripBom(value){
		if(typeof value !== 'string') return value;
		return value.replace(/^\uFEFF/, '').trim();
	}

	function safeParseJson(value){
		if(typeof value !== 'string') return value;
		let parsed = stripBom(value);
		for(let i=0;i<3;i++){
			if(typeof parsed !== 'string') return parsed;
			const t = parsed.trim();
			if(!t) return t;
			if(!((t.startsWith('{') && t.endsWith('}')) || (t.startsWith('[') && t.endsWith(']')))){
				return parsed;
			}
			try{
				parsed = JSON.parse(t);
			}catch(e){
				return value;
			}
		}
		return parsed;
	}

	function normalizeMessageText(value){
		if(value === null || value === undefined) return '';
		let text = String(value);
		text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
		text = text.replace(/\n{3,}/g, '\n\n');
		text = text.replace(/[\t ]{2,}/g, ' ');
		return text.trim();
	}

	function formatFiscalPayload(payload){
		if(payload === null || payload === undefined) return '';
		if(typeof payload === 'string'){
			const parsed = safeParseJson(payload);
			if(parsed !== payload){
				return formatFiscalPayload(parsed);
			}
			return normalizeMessageText(payload);
		}
		if(Array.isArray(payload) || typeof payload === 'object'){
			try{
				return JSON.stringify(payload, null, 2);
			}catch(e){
				return normalizeMessageText(String(payload));
			}
		}
		return normalizeMessageText(String(payload));
	}

	function extractFiscalMessage(payload){
		if(payload === null || payload === undefined) return '';
		if(typeof payload === 'string'){
			const parsed = safeParseJson(payload);
			if(parsed !== payload){
				return extractFiscalMessage(parsed);
			}
			return normalizeMessageText(payload);
		}
		if(Array.isArray(payload)){
			for(const item of payload){
				const msg = extractFiscalMessage(item);
				if(msg) return msg;
			}
			return formatFiscalPayload(payload);
		}
		if(typeof payload === 'object'){
			const preferredKeys = ['mensagem','message','xMotivo','error','erro','detail','details','title'];
			for(const key of preferredKeys){
				if(payload[key]){
					const msg = extractFiscalMessage(payload[key]);
					if(msg) return msg;
				}
			}

			const grupos = [
				payload.infProt,
				payload.protMDFe && payload.protMDFe.infProt,
				payload.protNFe && payload.protNFe.infProt,
				payload.protCTe && payload.protCTe.infProt,
				payload.infEvento,
				payload.retEvento && payload.retEvento.infEvento,
				payload.infInut,
				payload
			];

			for(const grupo of grupos){
				if(grupo && (grupo.cStat || grupo.xMotivo)){
					return normalizeMessageText('[' + (grupo.cStat || '---') + '] - ' + (grupo.xMotivo || 'Sem detalhamento'));
				}
			}

			if(payload.payload){
				const msg = extractFiscalMessage(payload.payload);
				if(msg) return msg;
			}
			if(payload.responseJSON){
				const msg = extractFiscalMessage(payload.responseJSON);
				if(msg) return msg;
			}
			if(payload.responseText){
				const msg = extractFiscalMessage(payload.responseText);
				if(msg) return msg;
			}

			return formatFiscalPayload(payload);
		}
		return '';
	}

	function showFiscalError(title, payload, fallback){
		const msg = extractFiscalMessage(payload) || fallback || 'Algo deu errado';
		if(typeof window.swal === 'function'){
			return window.swal(title || 'Erro', msg, 'error');
		}
		window.alert((title || 'Erro') + ': ' + msg);
		return null;
	}

	window.stripBom = window.stripBom || stripBom;
	window.safeParseJson = window.safeParseJson || safeParseJson;
	window.normalizeMessageText = window.normalizeMessageText || normalizeMessageText;
	window.formatFiscalPayload = window.formatFiscalPayload || formatFiscalPayload;
	window.extractFiscalMessage = window.extractFiscalMessage || extractFiscalMessage;
	window.showFiscalError = window.showFiscalError || showFiscalError;
})(window);
