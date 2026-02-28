const triggerExport = (dataset, format) => {
    window.location.href = `export.php?dataset=${dataset}&format=${format}`;
};
