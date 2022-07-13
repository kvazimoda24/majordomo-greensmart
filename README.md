#Что это#

Это модуль для системы умного дома MajorDoMo. Он предназначен для управления китайскими кондиционерами Green, Airweel и похожих, в которых есть модуль WiFi.
Перед привязкой кондиционера к модулю необходимо подключить кондиционер к вашей сети WiFi. Делается это через родное приложение.
После этого необходимо в настройках модуля указать широковещательный (broadcast) IP адрес. Это необходимо для того, чтобы модуль смог найти все кондиционеры в сети.
Дальше жмём "Искать" и через секунд 5-10 получаем список всех найденных кондиционеров.
Напротив необходимого нам кондиционера жмём кнопку добавить. В открывшемся окне можно задать своё название. Это название будет прописано в кондиционер, и в дальнейшем, если вдруг понадобится снова выполнить поиск, проще будет понять, какой кондиционер где.

При добавлении кондиционера в систему, для него создаются основные параметры, которые можно увидеть во вкладке Данные.
Эти параметры можно удалять, если не нужны или добавить свои, если вдруг у вас какое-то особенное устройство.
Так же любой параметр можно прилинковать к свойству объекта в системе, и тем самым получить возможность управлять кондиционером из системы.

#Список параметров#

* `Pow`: состояние питания устройства
  * 0: выключено
  * 1: включено
  
* `Mod`: режим работы
  * 0: авто
  * 1: охлаждение
  * 2: осушение
  * 3: вентилятор
  * 4: подогрев
  
* "SetTem" и "TemUn": установка температуры и единицы измерения
  * если `TemUn` = 0, `SetTem` задаёт температуру в градусах Цельсия
  * если `TemUn` = 1, `SetTem` задаёт температуру в градусах Фаренгейта
  
* `WdSpd`: скорость вентилятора
  * 0: авто
  * 1: медленно
  * 2: чуть быстрее (недоступно на трёхскоростных устройствах)
  * 3: средняя скорость
  * 4: ещё чуть быстрее (недоступно на трёхскоростных устройствах)
  * 5: быстро

* `Air`: управляет подачей свежего воздуха (судя по всему, недоступно на большинстве устройств)
  * 0: выключено
  * 1: включено

* `Blo`: "Blow" или "X-Fan", при включении этой функции вентилятор продолжит работать некоторое время после выключения устройства. Это позволяет меньше "заваниваться" кондиционеру. Работает только в режимах осушения и охлаждения.

* `Health`: режим здоровья ("Cold plasma"), только для устройств с модулем генерации этой самой "холодной плазмы"
  * 0: выключено
  * 1: включено
  
* `SwhSlp`: режим сна, который постепенно меняет температуру в режимах охлаждения, подогрева и осушения
  * 0: выключено
  * 1: включено

* `SwingLfRig`: управляет режимом поворота горизонтальных воздушных лопастей (доступно на ограниченном количестве устройств)
  * 0: по умолчанию
  * 1: полное качение
  * 2-6: фиксированные положения от крайнего левого до крайнего правого

* `SwUpDn`: управляет режимом поворота вертикальных воздушных лопастей
  * 0: по умолчанию
  * 1: полное качение
  * 2: зафиксироваться в крайнем верхнем положении (1/5)
  * 3: зафиксироваться в среднем верхнем положении (2/5)
  * 4: зафиксироваться в среднем положении (3/5)
  * 5: зафиксироваться в среднем нижнем положении (4/5)
  * 6: зафиксироваться в крайнем нижнем положении (5/5)
  * 7: качаться в самой нижней области (5/5)
  * 8: качаться в средней нижней области (4/5)
  * 9: качаться в средней области (3/5)
  * 10: качаться в средней верхней области (2/5)
  * 11: качаться в  верхней области (1/5)

* `Quiet`: управляет тихим режимом. В этом режиме вентилятор замедляется до минимальной скорости. Недоступно в режимах осушения и вентиляции.
  * 0: выключено
  * 1: включено
  
* `Tur`: устанавливает максимальную скорость вентилятора. Пока включена эта функция, скорость вентилятора изменить нельзя. Функция доступна только в режимах осушения и охлаждения.
  * 0: выключено
  * 1: включено

* `StHt`: поддерживает стабильную температуру в помещении на уровне 8°C и предотвращает промерзание помещения за счёт включения обогрева. Нужно на случай долгого отсутсвия людей в суровую зиму.

* `HeatCoolType`: неизвестно

* `TemRec`: этот параметр задаёт единицы измерения температуры - градусы Цельсия или Фаренгейта (смотри выше про установку температуры)

* `SvSt`: режим экономии энергии
  * 0: выключен
  * 1: включён

#Источники#

За основу была взята вот эта библиотека:
https://github.com/tomikaa87/gree-remote.git

Соответственно, там можно подробнее изучить протокол, на котром всё это работает, и даны несколько советов по поводу того, что кондиционеры постоянно стучатся на китайские серверы.

Я же портировал алгоритмы из этой библиотеки на PHP и обернул в модуль для MajorDoMo.

