from flask import Flask, render_template, request, redirect, url_for

app = Flask(__name__)

# Примерен списък за съхранение на резервации (в реална среда се използва база данни)
reservations = []

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/gallery')
def gallery():
    return render_template('gallery.html')

@app.route('/contact', methods=['GET', 'POST'])
def contact():
    if request.method == 'POST':
        # Взимане на данни от формата за резервация
        client_data = {
            name: request.form.get('name'),
            service: request.form.get('service'),
            date: request.form.get('date')
        }
        reservations.append(client_data)
        print(fНова резервация: {client_data})
        return redirect(url_for('home'))
    return render_template('contact.html')

if __name__ == '__main__':
    app.run(debug=True)
