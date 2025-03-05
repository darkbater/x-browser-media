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
// Запросить доступ к микрофону
console.log('Запрос доступа к микрофону...');
navigator.mediaDevices.getUserMedia({ audio: true })
  .then(stream => {
    console.log('Доступ к микрофону получен. Инициализация записи...');
    
    const mediaRecorder = new MediaRecorder(stream);
    let voiceChunks = [];
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');

    // Обработчик для сбора аудиоданных
    mediaRecorder.addEventListener("dataavailable", function(event) {
      console.log('Получены аудиодан размер размером:', event.data.size, 'байт');
      voiceChunks.push(event.data);
    });

    // Обработчик завершения записи
    mediaRecorder.addEventListener("stop", function() {
      console.log('Запись остановлена. Создание файла...');
      
      // Создаем аудиофайл
      const voiceBlob = new Blob(voiceChunks, { type: 'audio/wav' });
      console.log('Создан аудиофайл:', voiceBlob);

      // Формируем данные для отправки
      const formData = new FormData();
      formData.append('voice', voiceBlob, 'recording.wav');
      console.log('Форма данных подготовлена');

      // Отправка файла на сервер
      console.log('Начало загрузки файла...');
      fetch('http://example.com/voice', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Ответ получен, статус:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Успешная обработка:', data);
        // Остановить все треки микрофона
        stream.getTracks().forEach(track => track.stop());
      })
      .catch(error => console.error('Ошибка загрузки:', error));
      
      voiceChunks = []; // Очищаем буфер
    });

    // Управление кнопками
    startBtn.addEventListener('click', () => {
      console.log('Начало записи...');
      mediaRecorder.start();
      startBtn.disabled = true;
      stopBtn.disabled = false;
      voiceChunks = []; // Очищаем предыдущие данные
    });

    stopBtn.addEventListener('click', () => {
      console.log('Запрос на остановку записи...');
      if (mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        startBtn.disabled = false;
        stopBtn.disabled = true;
      }
    });

  })
  .catch(error => {
    console.error('Ошибка доступа к микрофону:', error);
    // Здесь можно добавить обработку ошибки для пользователя
  });

</script>
</html>