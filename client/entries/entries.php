<html>
<head>
</head>
<body>
<script>
    (async () => {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');

        if (!id) {
            console.error('Missing id parameter');
            return;
        }

        const res = await fetch(`/forms/api/form_entries.php?id=${encodeURIComponent(id)}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        });

        const data = await res.json();
        console.log(data);

    })();
</script>
</body>
</html>