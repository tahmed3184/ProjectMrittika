import sys
import pandas as pd
from sklearn.neighbors import NearestNeighbors

# Load the dataset
file_path = "crop_dataset.csv"
data = pd.read_csv(file_path)

# Trim whitespace from column names
data.columns = data.columns.str.strip()

# Convert numerical columns to numeric, coercing errors to NaN (if necessary)
feature_columns = ['Minimum ph', 'Maximum ph', 'Minimum Temp', 'Maximum Temp', 'Minimum N', 'Maximum N',
                   'Minimum P', 'Maximum P', 'Minimum K', 'Maximum K']
for col in feature_columns:
    data[col] = pd.to_numeric(data[col], errors='coerce')

# Calculate average values for nutrients and other relevant factors
data['Average ph'] = (data['Minimum ph'] + data['Maximum ph']) / 2
data['Average N'] = (data['Minimum N'] + data['Maximum N']) / 2
data['Average P'] = (data['Minimum P'] + data['Maximum P']) / 2
data['Average K'] = (data['Minimum K'] + data['Maximum K']) / 2


def predict_crop(ph, temp, n, p, k, month):
    ph = float(ph)
    temp = float(temp)
    n = float(n)
    p = float(p)
    k = float(k)

    # Convert month to numeric
    month_map = {
        'January': 1, 'February': 2, 'March': 3, 'April': 4,
        'May': 5, 'June': 6, 'July': 7, 'August': 8,
        'September': 9, 'October': 10, 'November': 11, 'December': 12
    }
    month = month_map.get(month.capitalize(), None)  # Capitalize for case-insensitivity
    if month is None:
        print("Invalid month. Please provide a valid month.")
        return []

    # Filter based on pH, temperature, and month
    filtered_data = data[(data['Minimum ph'] <= ph) & (data['Maximum ph'] >= ph)]
    filtered_data = filtered_data[(filtered_data['Minimum Temp'] <= temp) & (filtered_data['Maximum Temp'] >= temp)]
    filtered_data = filtered_data[(filtered_data['Start Month'] <= month) & (filtered_data['End Month'] >= month)]

    if filtered_data.empty:
        return []

    # Use KNN to find closest crops based on nutrient levels
    features = filtered_data[['Average N', 'Average P', 'Average K']]
    labels = filtered_data['Name']
    input_features = pd.DataFrame([[n, p, k]], columns=['Average N', 'Average P', 'Average K'])

    num_results = min(5, len(features))
    neighbors = NearestNeighbors(n_neighbors=num_results)
    neighbors.fit(features)
    distances, indices = neighbors.kneighbors(input_features)

    top_crops = labels.iloc[indices[0]].values
    return top_crops


def calculate_fertilizer_requirements(ph, n, p, k, desired_crop):
    ph = float(ph)
    n = float(n)
    p = float(p)
    k = float(k)

    crop_data = data[data['Name'] == desired_crop]
    if crop_data.empty:
        print(f"Crop '{desired_crop}' not found in the dataset.")
        return None

    crop_data = crop_data.iloc[0]  # Get the first matching row
    optimal_ph = crop_data['Average ph']
    optimal_n = crop_data['Average N']
    optimal_p = crop_data['Average P']
    optimal_k = crop_data['Average K']

    ph_diff = optimal_ph - ph
    n_diff = optimal_n - n
    p_diff = optimal_p - p
    k_diff = optimal_k - k

    lime_required = ph_diff * 1000
    n_required = n_diff * 10
    p_required = p_diff * 10
    k_required = k_diff * 10

    return {
        'Calcium Carbonate': lime_required if ph_diff > 0 else 0,
        'Elemental Sulfur': -lime_required if ph_diff < 0 else 0,
        'Urea or Ammonium Nitrate': n_required if n_diff > 0 else 0,
        'Triple Superphosphate or DAP': p_required if p_diff > 0 else 0,
        'Potassium Chloride': k_required if k_diff > 0 else 0,
    }


# Input values
ph = float(sys.argv[1])
n = float(sys.argv[2])
p = float(sys.argv[3])
k = float(sys.argv[4])
temp = float(sys.argv[5])
month = sys.argv[6]  # Month passed as a string
desired_crop = sys.argv[7]

# Predict crops
predicted_crops = predict_crop(ph, temp, n, p, k, month)
print("Predicted Crops:")
for idx, crop in enumerate(predicted_crops, 1):
    print(f"{idx}. {crop}")

# Calculate fertilizer requirements
fertilizer_requirements = calculate_fertilizer_requirements(ph, n, p, k, desired_crop)
if fertilizer_requirements is not None:
    print(f"\nFertilizer Requirements for {desired_crop}")
    for nutrient, amount in fertilizer_requirements.items():
        print(f"{nutrient}: {amount:.2f} kg/ha")
