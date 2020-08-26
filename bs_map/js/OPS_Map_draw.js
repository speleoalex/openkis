//console.log("load OPS_Map.js");

OPS_Map.InitDraw = function () {
    OPS_Map.DrawShape = "Circle";
    //layer Disegno----->
    OPS_Map.Drawsource = new ol.source.Vector();
    OPS_Map.DrawVectorLayer = new ol.layer.Vector({
        title: "Aree",
        source: OPS_Map.Drawsource,
        style: new ol.style.Style({
            fill: new ol.style.Fill({
                color: 'rgba(255, 255, 255, 0.2)'
            }),
            stroke: new ol.style.Stroke({
                color: '#ffcc33',
                width: 2
            }),
            image: new ol.style.Circle({
                radius: 7,
                fill: ol.style.Fill({
                    color: '#ffcc33'
                })
            })
        })
    });
    OPS_Map.map.addLayer(OPS_Map.DrawVectorLayer);
//layer Disegno-----<
    OPS_Map.Drawmodify = new ol.interaction.Modify({source: OPS_Map.Drawsource});
    OPS_Map.map.addInteraction(OPS_Map.Drawmodify);


    OPS_Map.DrawaddInteractions();
};

OPS_Map.DrawaddInteractions = function () {
    OPS_Map.draw = new ol.interaction.Draw({
        source: OPS_Map.Drawsource,
        type: OPS_Map.DrawShape
    });
    OPS_Map.map.addInteraction(OPS_Map.draw);
    OPS_Map.snap = new ol.interaction.Snap({source: OPS_Map.Drawsource});
    OPS_Map.map.addInteraction(OPS_Map.snap);
};

OPS_Map.DrawShape = function (shape) {
    OPS_Map.DrawShape=shape;
    OPS_Map.map.removeInteraction(OPS_Map.draw);
    OPS_Map.map.removeInteraction(OPS_Map.snap);
    OPS_Map.DrawaddInteractions();
}