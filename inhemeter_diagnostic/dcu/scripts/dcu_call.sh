echo "=== introspect inhe.dcu.traff ==="
gdbus introspect --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff 2>&1 | head -120
echo "=== introspect inhe.dcu.downlink ==="
gdbus introspect --system --dest inhe.dcu.downlink --object-path /inhe/dcu/downlink 2>&1 | head -120
echo "=== getDownLinkPara ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getDownLinkPara 2>&1
echo "=== getDownLinkInfo_Communication ==="
gdbus call --system --dest inhe.dcu.downlink --object-path /inhe/dcu/downlink --method inhe.dcu.downlink.getDownLinkInfo_Communication 2>&1
echo "=== getDownLinkInfo_Channel ==="
gdbus call --system --dest inhe.dcu.traff --object-path /inhe/dcu/traff --method inhe.dcu.traff.getDownLinkInfo_Channel 2>&1
