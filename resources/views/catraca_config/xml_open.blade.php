
<DATAPACKET Version="2.0">
	<METADATA>
		<FIELDS>
			<FIELD attrname="ID_COMANDA" fieldtype="string" WIDTH="20" />
			<FIELD attrname="EVENTO" fieldtype="string" WIDTH="1" />
		</FIELDS>
		<PARAMS />
	</METADATA>
	<ROWDATA>
		<ROW RowState="4" ID_COMANDA="{{$item->comanda}}" EVENTO="L" />
	</ROWDATA>
</DATAPACKET>