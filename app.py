from flask import Flask, render_template, request,jsonify
import csv
import pyads
import time
import threading
PLC_IP ={}

PLC_IP['4'] = '192.168.0.10.1.1'
PLC_IP['5'] = '172.18.236.172.1.1'
PLC_PORT = pyads.PORT_TC3PLC1

WriteCar=''
WriteCnt=0
structure_def = (
   ('PositionNo', pyads.PLCTYPE_WORD, 10000),
   ('PositionX', pyads.PLCTYPE_DINT, 10000),
   ('PositionY', pyads.PLCTYPE_DINT, 10000),
   ('PositionZ', pyads.PLCTYPE_DINT, 10000),
   ('PositionNotice', pyads.PLCTYPE_WORD, 10000)
)

app = Flask(__name__)
def CheckTimer():
    global WriteCar
    global WriteCnt
    while True:

        if WriteCar!='':
            WriteCnt+=1 
            try:
                with pyads.Connection(PLC_IP[WriteCar], PLC_PORT) as plc:
                    bAGVCWritePositionDataOK=plc.read_by_name('GVL.bAGVCWritePositionDataOK')
                    bAGVCNeedWritePositionData=plc.read_by_name('GVL.bAGVCNeedWritePositionData')
                    if  bAGVCWritePositionDataOK==True or bAGVCNeedWritePositionData==False or WriteCnt > 30:
                        WriteCar=''
                        WriteCnt=0    
                        plc.write_by_name('GVL.bAGVCNeedWritePositionData', False)            
                        plc.close()
            except Exception as e:
                print(f"An error occurred: {e}")    

        
        time.sleep(10)
def start_check_timer():
    thread = threading.Thread(target=CheckTimer)
    thread.start()


def set_plc_data():
    try:    
        with pyads.Connection(PLC_IP['4'], PLC_PORT) as plc:
            plc.write_by_name('GVL.bAGVCReadPositionData', False)
            plc.write_by_name('GVL.bAGVCNeedWritePositionData', False)
            plc.close()        
    except Exception as e:
        print(f"An error occurred 4: {e}") 

    try:   
        with pyads.Connection( PLC_IP['5'], PLC_PORT) as plc:
            plc.write_by_name('GVL.bAGVCReadPositionData', False)
            plc.write_by_name('GVL.bAGVCNeedWritePositionData', False)
            plc.close()
    except Exception as e:
        print(f"An error occurred 5: {e}") 

def add_cors_headers(response):
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Headers']= '*'
    response.headers['Access-Control-Allow-Methods']= '*'
    return response


# 設置CORS的應用程式攔截器
app.after_request(add_cors_headers)
@app.route('/')
def index():
    return render_template('index.html')
@app.route('/GetPoint', methods=["GET", "POST"])
def GetPoint():
    carno=4
    if request.method == 'GET':
        # 從GET請求中獲取名為'param'的參數值
        carno = request.args.get('carno')
        #print( f'GET 方法，參數值為: {param_value}')
    elif request.method == 'POST':
        data = request.get_json()
        carno=data['carno']
        print(  'POST 方法')
    CarMap = {}
    Point={}
    CarMapCsv=[]
    
    #print('Received data:', data)
    
    try: 
        plc = pyads.Connection(PLC_IP[carno], PLC_PORT)
        plc.open()
        plc.write_by_name("GVL.bAGVCNeedWritePositionData", False) #啟動現場寫入bit
        plc.write_by_name("GVL.bAGVCReadPositionData", True)
        time.sleep(0.5)
        for i in range(1, 11):
            CarMap[i] = plc.read_structure_by_name("GVL.PositionTotalFloorForAGVC[" + str(i) + "]", structure_def)
            print(f"{i-1}F")
            for j in range(0, len(CarMap[i]['PositionNo'])):
                if CarMap[i]['PositionNo'][j]>0:
                    print(f"{i},{CarMap[i]['PositionNo'][j]},{CarMap[i]['PositionX'][j]},{CarMap[i]['PositionY'][j]},{CarMap[i]['PositionZ'][j]},{CarMap[i]['PositionNotice'][j]}")
                    new_point = {}
                    new_point['floor_no']=i
                    new_point['Tag_ID']=CarMap[i]['PositionNo'][j]
                    new_point['X']=CarMap[i]['PositionX'][j]
                    new_point['Y']=CarMap[i]['PositionY'][j]
                    new_point['Z']=CarMap[i]['PositionZ'][j]
                    new_point['PositionNotice']=CarMap[i]['PositionNotice'][j]
                    CarMapCsv.append(new_point)
        plc.write_by_name("GVL.bAGVCReadPositionData", False)
    
    except :
        pass
    finally:
        plc.close()

   
    return jsonify(CarMapCsv)
@app.route('/SetPoint', methods=["POST"])   
def SetPoint():
    global WriteCar
    global WriteCnt
    data = request.get_json()
    carno=data['carno']
    points=data['points']
    floorid=data['floorid']
    print(points)
    CarMap = {}
    status={}
    status['status']=0
    plc = pyads.Connection(PLC_IP[data['carno']], PLC_PORT)
    plc.open()
    plc.write_by_name("GVL.bAGVCNeedWritePositionData", False) #關閉現場寫入bit
    plc.write_by_name("GVL.bAGVCReadPositionData", True)       #讀取現場的資料 
    time.sleep(0.5)
    plc.write_by_name("GVL.bAGVCReadPositionData", False)
    print(len(points))
    CarMap = plc.read_structure_by_name("GVL.PositionTotalFloorForAGVC[" + str(floorid) + "]", structure_def)
    print(len(CarMap['PositionNotice']))
    for j in range(0, 10000):
        if  j<=len(points):            
            print(f"{CarMap['PositionNo'][j]}->{int(points[j]['tagID'])}")
            CarMap['PositionNo'][j]=int(points[j]['tagID'])
            CarMap['PositionX'][j]=int(points[j]['x'])
            CarMap['PositionY'][j]=int(points[j]['y'])
            CarMap['PositionZ'][j]=0
            CarMap['PositionNotice'][j]=0
        else :
            CarMap['PositionNo'][j]=0
            CarMap['PositionX'][j]=0
            CarMap['PositionY'][j]=0
            CarMap['PositionZ'][j]=0
            CarMap['PositionNotice'][j]=0
    print(len(CarMap['PositionNotice']))
    plc.write_structure_by_name("GVL.PositionTotalFloorForAGVC[" + str(floorid) + "]",CarMap,structure_def)    
    plc.write_by_name("GVL.bAGVCNeedWritePositionData", True) #啟動現場寫入bit
    WriteCar=carno
    WriteCnt=0
        #CarMap[i] = plc.read_structure_by_name("GVL.PositionTotalFloorForAGVC[" + str(i) + "]", structure_def)

    #plc.write_by_name("GVL.bAGVCReadPositionData", False)
    plc.close()
    return jsonify(status)
@app.route('/checkmap', methods=["POST","GET"])   
def checkmap():
    global WriteCnt
    global WriteCar
  
    status={}
    status['status']=0  
    status['bAGVCNeedWritePositionData']=False
    status['bAGVCWritePositionDataOK']= False 
    print(WriteCar)
    if WriteCar!='' :
        plc = pyads.Connection(PLC_IP[WriteCar], PLC_PORT)
        plc.open()
        status['bAGVCReadPositionData']=plc.read_by_name("GVL.bAGVCReadPositionData")
        status['bAGVCNeedWritePositionData']=plc.read_by_name("GVL.bAGVCNeedWritePositionData")
        status['bAGVCWritePositionDataOK']=plc.read_by_name("GVL.bAGVCWritePositionDataOK")    
        if (status['bAGVCNeedWritePositionData']==True and status['bAGVCWritePositionDataOK']==True):
            plc.write_by_name("GVL.bAGVCNeedWritePositionData", False)
        plc.close()
    status['WriteCar']=WriteCar
    status['WriteCnt']=WriteCnt

    return jsonify(status)       
if __name__ == '__main__':
    start_check_timer()
    set_plc_data()    
    print(123)
    app.run(host="0.0.0.0",debug=False,port=8000)
