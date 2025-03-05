<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
<script>
navigator.mediaDevices.getUserMedia({ audio: true })
  .then(stream => {
    const mediaRecorder = new MediaRecorder(stream);
    let voice = [];

    mediaRecorder.addEventListener("dataavailable", function(event) {
      voice.push(event.data);
    });

    mediaRecorder.addEventListener("stop", function() {
      const voiceBlob = new Blob(voice, { type: 'audio/wav' });
      
      let fd = new FormData();
      fd.append('voice', voiceBlob);

      fetch('http://localhost:8000/voice', {
        method: 'POST',
        body: fd
      })
      .then(response => response.json())
      .then(data => console.log(data))
      .catch(error => console.error('Error:', error));
    });

    // Добавьте кнопки для запуска и остановки записи
  })
  .catch(error => console.error('Error:', error));

</script>
</html>